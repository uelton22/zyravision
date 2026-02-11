<?php

namespace App\Http\Controllers;

use App\Jobs\StartStreamJob;
use App\Jobs\StopStreamJob;
use App\Models\Camera;
use App\Models\Stream;
use App\Services\Streaming\StreamManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StreamController extends Controller
{
    public function __construct(
        private StreamManager $streamManager,
    ) {}

    /**
     * Start a stream for a camera.
     */
    public function start(Request $request, Camera $camera): RedirectResponse
    {
        $user = $request->user();

        // Authorization: must own the camera or be super-admin
        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403, 'Sem permissao para iniciar stream desta camera.');
        }

        // Check if camera is active
        if (! $camera->is_active) {
            return redirect()->back()->with('error', 'Camera inativa. Ative a camera antes de iniciar o stream.');
        }

        // Check for existing active/running stream
        $existing = Stream::forCamera($camera->id)->running()->first();
        if ($existing) {
            return redirect()->back()->with('info', 'Stream ja esta ativo para esta camera.');
        }

        // If there's an error stream, clean it up before starting fresh
        Stream::forCamera($camera->id)
            ->where('status', Stream::STATUS_ERROR)
            ->delete();

        // Check concurrent streams quota (skip for super-admin)
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
                return redirect()->back()->with('error', "Limite de streams simultaneos atingido ({$maxStreams}).");
            }
        }

        // Dispatch job to start stream
        StartStreamJob::dispatch($camera->id);

        return redirect()->back()->with('success', 'Stream sendo iniciado. Aguarde alguns segundos...');
    }

    /**
     * Stop a stream for a camera.
     */
    public function stop(Request $request, Camera $camera): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403, 'Sem permissao para parar stream desta camera.');
        }

        $stream = Stream::forCamera($camera->id)->running()->first();

        if (! $stream) {
            return redirect()->back()->with('info', 'Nenhum stream ativo para esta camera.');
        }

        StopStreamJob::dispatch($stream->id);

        return redirect()->back()->with('success', 'Stream sendo parado...');
    }

    /**
     * Get stream status for a camera (JSON response for polling).
     * Returns error/reconnecting state so the frontend can show proper feedback.
     */
    public function status(Request $request, Camera $camera)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        // Refresh camera to get latest is_online status
        $camera->refresh();

        // Check for running streams first
        $stream = Stream::forCamera($camera->id)->running()->first();

        if ($stream) {
            $status = $this->streamManager->getStreamStatus($stream);

            // Refresh stream after getStreamStatus may have changed it
            $stream->refresh();

            return response()->json([
                'active' => $stream->status === Stream::STATUS_ACTIVE,
                'reconnecting' => in_array($stream->status, [Stream::STATUS_RECONNECTING, Stream::STATUS_STARTING]),
                'camera_online' => (bool) $camera->is_online,
                'stream' => $status,
            ]);
        }

        // Check for error streams (auto-recovering in background)
        $errorStream = Stream::forCamera($camera->id)
            ->where('status', Stream::STATUS_ERROR)
            ->latest('started_at')
            ->first();

        if ($errorStream) {
            return response()->json([
                'active' => false,
                'reconnecting' => true,
                'camera_online' => false,
                'stream' => [
                    'stream_id' => $errorStream->id,
                    'status' => $errorStream->status,
                    'error_message' => $errorStream->error_message,
                    'hls_url' => $errorStream->hls_url,
                    'webrtc_url' => $errorStream->webrtc_url,
                    'recovery_attempt' => $errorStream->metadata['recovery_attempt'] ?? 0,
                ],
            ]);
        }

        return response()->json([
            'active' => false,
            'reconnecting' => false,
            'camera_online' => (bool) $camera->is_online,
            'stream' => null,
        ]);
    }
}
