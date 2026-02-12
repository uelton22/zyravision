<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StreamResource;
use App\Jobs\StartStreamJob;
use App\Jobs\StopStreamJob;
use App\Models\Camera;
use App\Models\Stream;
use App\Services\Streaming\StreamManager;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    use ApiResponder;

    public function __construct(
        private StreamManager $streamManager,
    ) {}

    public function start(Request $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        if (! $camera->is_active) {
            return $this->error('CAMERA_INACTIVE', 'Camera is not active. Enable it first.', 422);
        }

        $existing = Stream::forCamera($camera->id)->running()->first();
        if ($existing) {
            return $this->success([
                'message' => 'Stream is already active.',
                'stream' => new StreamResource($existing),
            ]);
        }

        // Clean up error streams
        Stream::forCamera($camera->id)
            ->where('status', Stream::STATUS_ERROR)
            ->delete();

        // Quota check
        if (! $user->isSuperAdmin() && $camera->tenant_id) {
            $activeCount = Stream::forTenant($camera->tenant_id)->running()->count();
            $maxStreams = config('streaming.limits.max_concurrent_streams', 50);

            $tenant = $camera->tenant;
            if ($tenant) {
                $plan = $tenant->currentPlan();
                if ($plan) {
                    $maxStreams = $plan->max_concurrent_streams;
                }
            }

            if ($activeCount >= $maxStreams) {
                return $this->error('QUOTA_EXCEEDED', "Concurrent streams limit reached ({$maxStreams}).", 429);
            }
        }

        StartStreamJob::dispatch($camera->id);

        return $this->success([
            'message' => 'Stream is being started. Poll the status endpoint for updates.',
            'camera_id' => $camera->id,
        ], status: 202);
    }

    public function stop(Request $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $stream = Stream::forCamera($camera->id)->running()->first();

        if (! $stream) {
            return $this->error('NO_ACTIVE_STREAM', 'No active stream found for this camera.', 404);
        }

        StopStreamJob::dispatch($stream->id);

        return $this->success(['message' => 'Stream is being stopped.'], status: 202);
    }

    public function status(Request $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $camera->refresh();

        $stream = Stream::forCamera($camera->id)->running()->first();

        if ($stream) {
            $status = $this->streamManager->getStreamStatus($stream);
            $stream->refresh();

            return $this->success([
                'active' => $stream->status === Stream::STATUS_ACTIVE,
                'reconnecting' => in_array($stream->status, [Stream::STATUS_RECONNECTING, Stream::STATUS_STARTING]),
                'camera_online' => (bool) $camera->is_online,
                'stream' => new StreamResource($stream),
                'mediamtx_status' => $status,
            ]);
        }

        $errorStream = Stream::forCamera($camera->id)
            ->where('status', Stream::STATUS_ERROR)
            ->latest('started_at')
            ->first();

        if ($errorStream) {
            return $this->success([
                'active' => false,
                'reconnecting' => true,
                'camera_online' => false,
                'stream' => new StreamResource($errorStream),
                'recovery_attempt' => $errorStream->metadata['recovery_attempt'] ?? 0,
            ]);
        }

        return $this->success([
            'active' => false,
            'reconnecting' => false,
            'camera_online' => (bool) $camera->is_online,
            'stream' => null,
        ]);
    }
}
