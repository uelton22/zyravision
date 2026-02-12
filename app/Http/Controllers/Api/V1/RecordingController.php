<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RecordingResource;
use App\Models\Camera;
use App\Models\Recording;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecordingController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Recording::query();

        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->filled('camera_id')) {
            $query->where('camera_id', $request->query('camera_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('from')) {
            $query->where('started_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('started_at', '<=', $request->query('to'));
        }

        if ($request->has('include') && str_contains($request->query('include'), 'camera')) {
            $query->with('camera');
        }

        $perPage = min((int) $request->query('per_page', 15), 100);

        $recordings = $query->orderByDesc('started_at')->paginate($perPage);

        return response()->json([
            'data' => RecordingResource::collection($recordings),
            'meta' => [
                'api_version' => 'v1',
                'current_page' => $recordings->currentPage(),
                'per_page' => $recordings->perPage(),
                'total' => $recordings->total(),
                'last_page' => $recordings->lastPage(),
            ],
            'links' => [
                'first' => $recordings->url(1),
                'last' => $recordings->url($recordings->lastPage()),
                'prev' => $recordings->previousPageUrl(),
                'next' => $recordings->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, Recording $recording): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $recording->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $recording->load(['camera', 'stream']);

        return $this->resource(new RecordingResource($recording));
    }

    public function destroy(Request $request, Recording $recording): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $recording->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $recording->update(['status' => Recording::STATUS_DELETED]);
        $recording->delete();

        return $this->success(['message' => 'Recording deleted successfully.']);
    }

    public function stream(Request $request, Recording $recording): StreamedResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $recording->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $path = $recording->storage_path;

        if (! file_exists($path)) {
            return $this->notFound('Recording file not found.');
        }

        $size = filesize($path);
        $headers = [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $size,
        ];

        // Byte-range support for seeking
        if ($request->hasHeader('Range')) {
            $range = $request->header('Range');
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
            $start = (int) $matches[1];
            $end = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $size - 1;
            $length = $end - $start + 1;

            return response()->stream(function () use ($path, $start, $length) {
                $fp = fopen($path, 'rb');
                fseek($fp, $start);
                echo fread($fp, $length);
                fclose($fp);
            }, 206, [
                'Content-Type' => 'video/mp4',
                'Content-Range' => "bytes {$start}-{$end}/{$size}",
                'Content-Length' => $length,
                'Accept-Ranges' => 'bytes',
            ]);
        }

        return response()->stream(function () use ($path) {
            readfile($path);
        }, 200, $headers);
    }

    public function download(Request $request, Recording $recording): StreamedResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $recording->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $path = $recording->storage_path;

        if (! file_exists($path)) {
            return $this->notFound('Recording file not found.');
        }

        $filename = sprintf(
            '%s_%s.mp4',
            $recording->camera?->slug ?? 'recording',
            $recording->started_at->format('Y-m-d_H-i-s')
        );

        return response()->download($path, $filename);
    }

    public function clip(Request $request, Recording $recording): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $recording->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $request->validate([
            'start' => 'required|numeric|min:0',
            'end' => 'required|numeric|gt:start',
        ]);

        // Clip creation would be dispatched as a job
        return $this->success([
            'message' => 'Clip creation queued.',
            'recording_id' => $recording->id,
            'start' => $request->start,
            'end' => $request->end,
        ], status: 202);
    }

    public function cameraRecordings(Request $request, Camera $camera): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            return $this->forbidden();
        }

        $query = $camera->recordings();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('from')) {
            $query->where('started_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('started_at', '<=', $request->query('to'));
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $recordings = $query->orderByDesc('started_at')->paginate($perPage);

        return response()->json([
            'data' => RecordingResource::collection($recordings),
            'meta' => [
                'api_version' => 'v1',
                'current_page' => $recordings->currentPage(),
                'per_page' => $recordings->perPage(),
                'total' => $recordings->total(),
                'last_page' => $recordings->lastPage(),
            ],
        ]);
    }
}
