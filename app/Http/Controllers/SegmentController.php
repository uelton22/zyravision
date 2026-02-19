<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\RecordingSegment;
use App\Models\StorageBackend;
use App\Services\Storage\StorageBackendManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SegmentController extends Controller
{
    public function __construct(
        private StorageBackendManager $storageManager,
    ) {}

    /**
     * GET /api/v1/cameras/recordings-summary
     *
     * Return cameras that have recordings with summary stats.
     * Used by the mobile app's recording list (camera-centric view).
     */
    public function camerasWithRecordings(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = RecordingSegment::query()->available();

        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $summaries = $query
            ->selectRaw("
                camera_id,
                COUNT(*) as total_segments,
                SUM(duration_seconds) as total_duration_seconds,
                SUM(file_size_bytes) as total_size_bytes,
                MIN(starts_at) as first_recording_at,
                MAX(ends_at) as last_recording_at,
                COUNT(DISTINCT DATE(starts_at)) as total_days
            ")
            ->groupBy('camera_id')
            ->orderByDesc('last_recording_at')
            ->get();

        $cameraIds = $summaries->pluck('camera_id');
        $cameras = Camera::whereIn('id', $cameraIds)->get()->keyBy('id');

        $data = $summaries->map(function ($summary) use ($cameras) {
            $camera = $cameras->get($summary->camera_id);
            if (! $camera) {
                return null;
            }

            $totalSeconds = (int) $summary->total_duration_seconds;
            $totalHours = round($totalSeconds / 3600, 1);

            return [
                'camera_id' => $summary->camera_id,
                'camera_name' => $camera->name,
                'camera_slug' => $camera->slug,
                'camera_status' => $camera->status,
                'is_online' => $camera->is_online,
                'is_recording' => $camera->is_recording,
                'total_segments' => (int) $summary->total_segments,
                'total_duration_seconds' => $totalSeconds,
                'total_duration_human' => $this->formatDuration($totalSeconds),
                'total_hours' => $totalHours,
                'total_size_bytes' => (int) $summary->total_size_bytes,
                'total_size_human' => $this->formatFileSize((int) $summary->total_size_bytes),
                'total_days' => (int) $summary->total_days,
                'first_recording_at' => $summary->first_recording_at,
                'last_recording_at' => $summary->last_recording_at,
            ];
        })->filter()->values();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/cameras/{camera}/timeline
     *
     * Return available segments for a camera within a time range.
     * Used by the frontend timeline player to know what footage is available.
     */
    public function timeline(Request $request, Camera $camera): JsonResponse
    {
        $this->authorizeCamera($camera);

        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        $start = $request->input('start')
            ? Carbon::parse($request->input('start'))
            : now()->subHours(24);

        $end = $request->input('end')
            ? Carbon::parse($request->input('end'))
            : now();

        $segments = RecordingSegment::forCamera($camera->id)
            ->available()
            ->inTimeRange($start, $end)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (RecordingSegment $s) => [
                'id' => $s->id,
                'starts_at' => $s->starts_at->toIso8601String(),
                'ends_at' => $s->ends_at?->toIso8601String(),
                'duration_seconds' => $s->duration_seconds,
                'file_size_bytes' => $s->file_size_bytes,
                'status' => $s->status,
                'has_local' => $s->isLocal(),
                'has_remote' => $s->isRemote(),
                'thumbnail_url' => $s->thumbnail_path,
            ]);

        // Build timeline ranges (contiguous segments grouped)
        $ranges = $this->buildTimelineRanges($segments->toArray());

        // Calculate cumulative playlist offset for each segment (for HLS seek mapping)
        $cumulativeOffset = 0;
        $gapThreshold = 120;
        $segmentsWithOffset = $segments->values()->map(function ($seg, $index) use ($segments, &$cumulativeOffset, $gapThreshold) {
            if ($index > 0) {
                $prevSeg = $segments->values()[$index - 1];
                $prevEnd = $prevSeg['ends_at'] ?? Carbon::parse($prevSeg['starts_at'])->addSeconds($prevSeg['duration_seconds'])->toIso8601String();
                $gap = Carbon::parse($prevEnd)->diffInSeconds(Carbon::parse($seg['starts_at']), false);

                if ($gap <= $gapThreshold) {
                    $cumulativeOffset += $prevSeg['duration_seconds'] ?: 60;
                } else {
                    $cumulativeOffset += $prevSeg['duration_seconds'] ?: 60;
                }
            }

            $seg['playlist_offset_seconds'] = $cumulativeOffset;
            return $seg;
        });

        return response()->json([
            'camera_id' => $camera->id,
            'range' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ],
            'segments' => $segmentsWithOffset,
            'ranges' => $ranges,
            'total_segments' => $segments->count(),
            'total_duration_seconds' => $segments->sum('duration_seconds'),
            'hls_playlist_url' => route('cameras.hls-playlist', [
                'camera' => $camera->id,
                'date' => Carbon::parse($start)->format('Y-m-d'),
            ]),
        ]);
    }

    /**
     * GET /api/cameras/{camera}/timeline/dates
     *
     * Return dates that have available recordings for a camera.
     * Used by the calendar picker in the frontend.
     */
    public function availableDates(Request $request, Camera $camera): JsonResponse
    {
        $this->authorizeCamera($camera);

        $dates = RecordingSegment::forCamera($camera->id)
            ->available()
            ->selectRaw("DATE(starts_at) as date, COUNT(*) as segment_count, SUM(duration_seconds) as total_seconds")
            ->groupByRaw('DATE(starts_at)')
            ->orderByDesc('date')
            ->limit(90)
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'segment_count' => (int) $row->segment_count,
                'total_hours' => round($row->total_seconds / 3600, 1),
            ]);

        return response()->json(['dates' => $dates]);
    }

    /**
     * GET /api/segments/{segment}/stream
     *
     * Stream a specific segment file (with range support for seeking).
     * Supports both .ts (MPEG-TS) and legacy .mp4 segments.
     */
    public function stream(Request $request, RecordingSegment $segment): BinaryFileResponse|StreamedResponse
    {
        $this->authorizeSegment($segment);

        $localFile = $this->resolveSegmentFile($segment);

        if (! $localFile) {
            abort(404, 'Segmento nao encontrado.');
        }

        $contentType = str_ends_with(strtolower($localFile), '.ts')
            ? 'video/MP2T'
            : 'video/mp4';

        return response()->file($localFile, [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * GET /api/v1/cameras/{camera}/playback.m3u8?date=2026-02-17
     *
     * Generate a dynamic HLS (M3U8) playlist for a camera on a specific date.
     * This enables continuous playback across all segments without flickering.
     * Uses #EXT-X-PROGRAM-DATE-TIME for real-world time mapping.
     * Uses #EXT-X-DISCONTINUITY to handle gaps in recording.
     */
    public function hlsPlaylist(Request $request, Camera $camera): \Illuminate\Http\Response
    {
        $this->authorizeCamera($camera);

        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'start' => 'nullable|date',
            'end' => 'nullable|date',
            'start_from' => 'nullable|integer|min:0',
        ]);

        if ($request->has('date')) {
            $date = Carbon::parse($request->input('date'));
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();
        } else {
            $start = $request->input('start')
                ? Carbon::parse($request->input('start'))
                : now()->startOfDay();
            $end = $request->input('end')
                ? Carbon::parse($request->input('end'))
                : now()->endOfDay();
        }

        $segments = RecordingSegment::forCamera($camera->id)
            ->available()
            ->inTimeRange($start, $end)
            ->orderBy('starts_at')
            ->get();

        // start_from: cumulative playlist offset in seconds — skip segments before it
        $startFrom = (int) $request->input('start_from', 0);
        if ($startFrom > 0) {
            $cumulative = 0;
            $segments = $segments->filter(function ($seg) use (&$cumulative, $startFrom) {
                $dur = $seg->duration_seconds ?: 60;
                $segEnd = $cumulative + $dur;
                if ($segEnd <= $startFrom) {
                    $cumulative += $dur;
                    return false;
                }
                $cumulative += $dur;
                return true;
            })->values();
        }

        $gapThreshold = 120;

        $maxDuration = 0;
        foreach ($segments as $seg) {
            $dur = $seg->duration_seconds ?: 60;
            if ($dur > $maxDuration) {
                $maxDuration = $dur;
            }
        }
        $targetDuration = max(1, (int) ceil($maxDuration));

        $token = $request->bearerToken();
        $tokenQuery = $token ? '?token=' . urlencode($token) : '';

        $m3u8 = "#EXTM3U\n";
        $m3u8 .= "#EXT-X-VERSION:3\n";
        $m3u8 .= "#EXT-X-TARGETDURATION:{$targetDuration}\n";
        $m3u8 .= "#EXT-X-PLAYLIST-TYPE:VOD\n";
        $m3u8 .= "#EXT-X-MEDIA-SEQUENCE:0\n";

        $prevEnd = null;

        foreach ($segments as $segment) {
            $segStart = $segment->starts_at;
            $duration = $segment->duration_seconds ?: 60;

            if ($prevEnd !== null) {
                $gap = $prevEnd->diffInSeconds($segStart, false);
                if ($gap > $gapThreshold) {
                    $m3u8 .= "#EXT-X-DISCONTINUITY\n";
                }
            }

            $programDateTime = $segStart->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
            $m3u8 .= "#EXT-X-PROGRAM-DATE-TIME:{$programDateTime}\n";
            $m3u8 .= "#EXTINF:" . number_format($duration, 3, '.', '') . ",\n";
            $m3u8 .= url("/api/v1/segments/{$segment->id}/stream") . $tokenQuery . "\n";

            $prevEnd = $segment->ends_at ?? $segStart->copy()->addSeconds($duration);
        }

        $m3u8 .= "#EXT-X-ENDLIST\n";

        return response($m3u8, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'no-cache',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * GET /api/segments/{segment}/download
     *
     * Download a specific segment.
     */
    public function download(Request $request, RecordingSegment $segment): BinaryFileResponse
    {
        $this->authorizeSegment($segment);

        $localFile = $this->resolveSegmentFile($segment);

        if (! $localFile) {
            abort(404, 'Segmento nao encontrado.');
        }

        $camera = $segment->camera;
        $date = $segment->starts_at->format('Y-m-d_H-i-s');
        $ext = str_ends_with(strtolower($localFile), '.ts') ? 'ts' : 'mp4';
        $contentType = $ext === 'ts' ? 'video/MP2T' : 'video/mp4';
        $filename = "gravacao_{$camera->name}_{$date}.{$ext}";
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        return response()->download($localFile, $filename, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * GET /api/cameras/{camera}/playback?time=2026-02-10T13:00:00Z
     *
     * Find and redirect to the segment that contains the specified time.
     * This is the main entry point for timeline navigation.
     */
    public function playbackAtTime(Request $request, Camera $camera): JsonResponse
    {
        $this->authorizeCamera($camera);

        $request->validate([
            'time' => 'required|date',
        ]);

        $time = Carbon::parse($request->input('time'));

        // Find segment containing this time
        $segment = RecordingSegment::forCamera($camera->id)
            ->available()
            ->where('starts_at', '<=', $time)
            ->where(function ($q) use ($time) {
                $q->where('ends_at', '>=', $time)
                    ->orWhereNull('ends_at');
            })
            ->first();

        if (! $segment) {
            // Find closest segment after the requested time
            $segment = RecordingSegment::forCamera($camera->id)
                ->available()
                ->where('starts_at', '>=', $time)
                ->orderBy('starts_at')
                ->first();
        }

        if (! $segment) {
            return response()->json([
                'found' => false,
                'message' => 'Nenhuma gravacao encontrada para o horario solicitado.',
            ], 404);
        }

        // Calculate offset within the segment
        $offset = max(0, $time->diffInSeconds($segment->starts_at, false));

        return response()->json([
            'found' => true,
            'segment' => [
                'id' => $segment->id,
                'starts_at' => $segment->starts_at->toIso8601String(),
                'ends_at' => $segment->ends_at?->toIso8601String(),
                'duration_seconds' => $segment->duration_seconds,
                'stream_url' => url("/api/v1/segments/{$segment->id}/stream"),
            ],
            'offset_seconds' => $offset,
        ]);
    }

    /**
     * Resolve a segment file to a local path (local file or download from remote).
     * Funciona para gravacoes no disco (local) e na nuvem (remote).
     */
    private function resolveSegmentFile(RecordingSegment $segment): ?string
    {
        // 1. Preferir arquivo local se existir e for legivel
        if ($segment->local_path && File::isFile($segment->local_path) && File::isReadable($segment->local_path)) {
            return $segment->local_path;
        }

        // 2. Se nao tem local ou arquivo foi removido, baixar do storage remoto
        if ($segment->remote_path && $segment->storage_backend_id) {
            return $this->downloadFromRemote($segment);
        }

        return null;
    }

    /**
     * Download segment from remote storage to local cache.
     */
    private function downloadFromRemote(RecordingSegment $segment): ?string
    {
        $backend = StorageBackend::find($segment->storage_backend_id);

        if (! $backend) {
            return null;
        }

        try {
            $driver = $this->storageManager->getDriver($backend);

            // Cache directory
            $cacheDir = storage_path("app/segment_cache/{$segment->camera_id}");

            if (! File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }

            $localCache = $cacheDir . '/' . $segment->filename;

            // Already cached?
            if (File::exists($localCache)) {
                $localSize = File::size($localCache);
                if ($localSize > 0 && abs($localSize - $segment->file_size_bytes) < 1024) {
                    return $localCache;
                }
            }

            // Download
            $contents = $driver->retrieve($segment->remote_path);
            File::put($localCache, $contents);

            Log::debug('Segmento baixado do storage remoto.', [
                'segment_id' => $segment->id,
                'remote_path' => $segment->remote_path,
                'cache_path' => $localCache,
            ]);

            return $localCache;
        } catch (\Throwable $e) {
            Log::error('Erro ao baixar segmento do storage remoto.', [
                'segment_id' => $segment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build contiguous time ranges from segment list.
     */
    private function buildTimelineRanges(array $segments): array
    {
        if (empty($segments)) {
            return [];
        }

        $ranges = [];
        $currentRange = null;
        $gapThreshold = 120; // Max gap (seconds) to consider contiguous

        foreach ($segments as $seg) {
            $start = Carbon::parse($seg['starts_at']);
            $end = $seg['ends_at'] ? Carbon::parse($seg['ends_at']) : $start->copy()->addSeconds($seg['duration_seconds']);

            if (! $currentRange) {
                $currentRange = ['start' => $start->toIso8601String(), 'end' => $end->toIso8601String()];

                continue;
            }

            $prevEnd = Carbon::parse($currentRange['end']);
            $gap = $prevEnd->diffInSeconds($start, false);

            if ($gap <= $gapThreshold) {
                // Extend current range
                $currentRange['end'] = $end->toIso8601String();
            } else {
                // Gap detected, close current range and start new
                $ranges[] = $currentRange;
                $currentRange = ['start' => $start->toIso8601String(), 'end' => $end->toIso8601String()];
            }
        }

        if ($currentRange) {
            $ranges[] = $currentRange;
        }

        return $ranges;
    }

    private function authorizeCamera(Camera $camera): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403, 'Acesso negado.');
        }
    }

    private function authorizeSegment(RecordingSegment $segment): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $segment->tenant_id !== $user->tenant_id) {
            abort(403, 'Acesso negado.');
        }
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return number_format($bytes / 1024, 0) . ' KB';
    }
}
