<?php

namespace App\Http\Controllers;

use App\Jobs\StartRecordingJob;
use App\Jobs\StopRecordingJob;
use App\Models\Camera;
use App\Models\Recording;
use App\Models\RecordingSegment;
use App\Models\StorageBackend;
use App\Models\Tenant;
use App\Services\Recording\RecordingManager;
use App\Services\Storage\StorageBackendManager;
use App\Services\Streaming\MediaMtxClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecordingController extends Controller
{
    public function __construct(
        private RecordingManager $recordingManager,
        private MediaMtxClient $mediaMtxClient,
        private StorageBackendManager $storageManager,
    ) {}

    /**
     * Start recording for a camera.
     */
    public function start(Request $request, Camera $camera): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403, 'Sem permissao para gravar esta camera.');
        }

        if (! $camera->is_active) {
            return redirect()->back()->with('error', 'Camera inativa.');
        }

        if ($camera->is_recording) {
            return redirect()->back()->with('info', 'Camera ja esta gravando.');
        }

        // Check stream is active
        $camera->loadMissing('activeStream');
        if (! $camera->activeStream || ! $camera->activeStream->isRunning()) {
            return redirect()->back()->with('error', 'Inicie o stream antes de gravar.');
        }

        // Check storage quota
        $tenant = $camera->tenant;
        if ($tenant) {
            $usedGb = $this->recordingManager->calculateTenantStorage($tenant);
            $limitGb = $tenant->getStorageLimitGb();
            if ($usedGb >= $limitGb) {
                return redirect()->back()->with('error', "Limite de armazenamento atingido ({$usedGb}GB / {$limitGb}GB). Libere espaco ou faca upgrade do plano.");
            }
        }

        StartRecordingJob::dispatch($camera->id);

        return redirect()->back()->with('success', 'Gravacao sendo iniciada...');
    }

    /**
     * Stop recording for a camera.
     */
    public function stop(Request $request, Camera $camera): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403, 'Sem permissao para parar gravacao desta camera.');
        }

        if (! $camera->is_recording) {
            return redirect()->back()->with('info', 'Camera nao esta gravando.');
        }

        StopRecordingJob::dispatch($camera->id);

        return redirect()->back()->with('success', 'Gravacao sendo parada...');
    }

    /**
     * Get recording status (JSON for polling).
     */
    public function status(Request $request, Camera $camera)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $camera->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        $recording = Recording::forCamera($camera->id)->recording()->first();

        return response()->json([
            'is_recording' => $camera->is_recording,
            'recording' => $recording ? [
                'id' => $recording->id,
                'started_at' => $recording->started_at?->toDateTimeString(),
                'duration_seconds' => $recording->started_at
                    ? (int) $recording->started_at->diffInSeconds(now())
                    : 0,
                'file_size_bytes' => $recording->file_size_bytes,
            ] : null,
        ]);
    }

    /**
     * List cameras with recording segments.
     * Shows a summary of each camera's recording availability.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Get cameras with segment statistics
        $cameraQuery = Camera::query()->with('tenant:id,name');

        if (! $isSuperAdmin) {
            $cameraQuery->forTenant($user->tenant_id);
        }

        if ($tenantId = $request->input('tenant_id')) {
            $cameraQuery->where('tenant_id', $tenantId);
        }

        if ($search = $request->input('search')) {
            $cameraQuery->where('name', 'ilike', "%{$search}%");
        }

        $cameras = $cameraQuery->orderBy('name')->get();

        // Get segment stats per camera
        $segmentStats = RecordingSegment::query()
            ->whereIn('camera_id', $cameras->pluck('id'))
            ->whereIn('status', [RecordingSegment::STATUS_READY, RecordingSegment::STATUS_UPLOADED, RecordingSegment::STATUS_UPLOADING])
            ->selectRaw('camera_id, COUNT(*) as segment_count, SUM(file_size_bytes) as total_size, SUM(duration_seconds) as total_duration, MIN(starts_at) as first_segment, MAX(starts_at) as last_segment')
            ->groupBy('camera_id')
            ->get()
            ->keyBy('camera_id');

        $camerasWithRecordings = $cameras->map(function (Camera $camera) use ($segmentStats) {
            $stats = $segmentStats->get($camera->id);

            return [
                'id' => $camera->id,
                'name' => $camera->name,
                'slug' => $camera->slug,
                'is_active' => $camera->is_active,
                'is_online' => $camera->is_online,
                'is_recording' => $camera->is_recording,
                'continuous_recording' => $camera->continuous_recording,
                'tenant' => $camera->tenant ? ['id' => $camera->tenant->id, 'name' => $camera->tenant->name] : null,
                'segment_count' => $stats ? (int) $stats->segment_count : 0,
                'total_size' => $stats ? (int) $stats->total_size : 0,
                'total_duration' => $stats ? (int) $stats->total_duration : 0,
                'first_segment' => $stats?->first_segment,
                'last_segment' => $stats?->last_segment,
                'has_recordings' => $stats !== null,
            ];
        })->sortByDesc('has_recordings')->values();

        $tenants = $isSuperAdmin
            ? Tenant::orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Recordings/Index', [
            'cameras' => $camerasWithRecordings,
            'filters' => $request->only(['search', 'tenant_id']),
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Show recording detail with playback.
     */
    public function show(Recording $recording): Response
    {
        $this->authorizeRecording($recording);

        $recording->load('camera:id,name,slug', 'tenant:id,name', 'stream:id,mediamtx_path');

        // Collect all segment files for this recording (legacy)
        $segments = $this->getRecordingSegments($recording);

        // Build direct video URL (legacy single-file)
        $videoUrl = null;
        if (count($segments) > 0) {
            $videoUrl = route('recordings.stream', $recording);
        }

        // New segment system: get recording_segments for the camera in this recording's time range
        $timelineSegments = [];
        if ($recording->camera_id) {
            $timelineSegments = \App\Models\RecordingSegment::forCamera($recording->camera_id)
                ->available()
                ->orderBy('starts_at')
                ->when($recording->started_at && $recording->ended_at, function ($q) use ($recording) {
                    // Segments within this recording's time range
                    $q->inTimeRange($recording->started_at, $recording->ended_at);
                })
                ->limit(500)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'starts_at' => $s->starts_at->toIso8601String(),
                    'ends_at' => $s->ends_at?->toIso8601String(),
                    'duration_seconds' => $s->duration_seconds,
                    'file_size_bytes' => $s->file_size_bytes,
                    'status' => $s->status,
                    'stream_url' => route('segments.stream', $s),
                ])
                ->values()
                ->toArray();
        }

        return Inertia::render('Recordings/Show', [
            'recording' => $recording,
            'videoUrl' => $videoUrl,
            'segments' => $segments,
            'timelineSegments' => $timelineSegments,
            'downloadUrl' => route('recordings.download', $recording),
        ]);
    }

    /**
     * Stream recording MP4 file with range support for seeking.
     */
    public function stream(Request $request, Recording $recording): BinaryFileResponse|StreamedResponse
    {
        $this->authorizeRecording($recording);

        $file = $this->findRecordingFile($recording);
        if (! $file) {
            abort(404, 'Arquivo de gravacao nao encontrado.');
        }

        return response()->file($file, [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Download recording file.
     */
    public function download(Request $request, Recording $recording): BinaryFileResponse
    {
        $this->authorizeRecording($recording);

        $file = $this->findRecordingFile($recording);
        if (! $file) {
            abort(404, 'Arquivo de gravacao nao encontrado.');
        }

        $cameraName = $recording->camera?->name ?? 'camera';
        $date = $recording->started_at?->format('Y-m-d_H-i-s') ?? 'recording';
        $filename = "gravacao_{$cameraName}_{$date}.mp4";
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        return response()->download($file, $filename, [
            'Content-Type' => 'video/mp4',
        ]);
    }

    /**
     * Clip/trim a portion of the recording and download.
     */
    public function clip(Request $request, Recording $recording)
    {
        $this->authorizeRecording($recording);

        $request->validate([
            'start' => 'required|numeric|min:0',
            'end' => 'required|numeric|gt:start',
        ]);

        $file = $this->findRecordingFile($recording);
        if (! $file) {
            abort(404, 'Arquivo de gravacao nao encontrado.');
        }

        $start = (float) $request->input('start');
        $end = (float) $request->input('end');
        $duration = $end - $start;

        // Generate temp clip file
        $tempDir = storage_path('app/temp_clips');
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $cameraName = $recording->camera?->name ?? 'camera';
        $date = $recording->started_at?->format('Y-m-d_H-i-s') ?? 'clip';
        $clipFilename = "clip_{$cameraName}_{$date}_" . round($start) . '-' . round($end) . 's.mp4';
        $clipFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $clipFilename);
        $clipPath = $tempDir . '/' . $clipFilename;

        // Use ffmpeg to extract the clip - copy codec for speed
        $result = Process::timeout(120)->run([
            'ffmpeg', '-y',
            '-ss', (string) $start,
            '-i', $file,
            '-t', (string) $duration,
            '-c', 'copy',
            '-movflags', '+faststart',
            $clipPath,
        ]);

        if (! $result->successful() || ! File::exists($clipPath)) {
            return response()->json([
                'error' => 'Falha ao gerar recorte do video.',
                'details' => $result->errorOutput(),
            ], 500);
        }

        // Return download and schedule cleanup
        return response()->download($clipPath, $clipFilename, [
            'Content-Type' => 'video/mp4',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Delete a recording.
     */
    public function destroy(Request $request, Recording $recording): RedirectResponse
    {
        $this->authorizeRecording($recording);

        // Delete files
        $storagePath = $recording->storage_path;
        if ($storagePath && File::isDirectory($storagePath)) {
            File::deleteDirectory($storagePath);
        }

        $recording->update(['status' => Recording::STATUS_DELETED]);
        $recording->delete();

        // Recalculate storage quota
        $this->recordingManager->updateTenantStorageQuota($recording->tenant_id);

        return redirect()->route('recordings.index')
            ->with('success', 'Gravacao removida com sucesso.');
    }

    /**
     * Get playback URL for a recording (JSON) — legacy endpoint.
     */
    public function playback(Request $request, Recording $recording)
    {
        $this->authorizeRecording($recording);

        // Return the direct stream URL
        return response()->json([
            'playback_url' => route('recordings.stream', $recording),
        ]);
    }

    /**
     * Get MP4 files that belong to a specific recording, filtered by time range.
     *
     * Multiple recordings from the same camera share the same storage directory.
     * Each segment file has a modification time that we use to associate it with
     * the correct recording based on its started_at / ended_at window.
     *
     * @return \SplFileInfo[]
     */
    private function getRecordingFiles(Recording $recording): array
    {
        $storagePath = $recording->storage_path;
        if (! $storagePath || ! File::isDirectory($storagePath)) {
            return [];
        }

        $allFiles = File::files($storagePath);

        // Filter to .mp4 only
        $mp4Files = array_filter($allFiles, fn ($file) => str_ends_with(strtolower($file->getFilename()), '.mp4'));

        if (empty($mp4Files)) {
            return [];
        }

        // If no started_at, can't filter — return all (legacy fallback)
        if (! $recording->started_at) {
            return array_values($mp4Files);
        }

        // Time window: started_at - 60s buffer to ended_at + 120s buffer
        // Buffer accounts for MediaMTX segment flush timing
        $windowStart = $recording->started_at->timestamp - 60;
        $windowEnd = $recording->ended_at
            ? $recording->ended_at->timestamp + 120
            : time() + 120; // Still recording — include all from start onward

        $matched = [];
        foreach ($mp4Files as $file) {
            $fileMtime = $file->getMTime();

            // File was modified (finished writing) within the recording window
            if ($fileMtime >= $windowStart && $fileMtime <= $windowEnd) {
                $matched[] = $file;
            }
        }

        // Sort by modification time (chronological)
        usort($matched, fn ($a, $b) => $a->getMTime() <=> $b->getMTime());

        // If no files matched by time (e.g. timezone mismatch), try filename-based matching
        if (empty($matched)) {
            $matched = $this->matchFilesByFilename($mp4Files, $recording);
        }

        return $matched;
    }

    /**
     * Fallback: match files by parsing timestamp from filename (YYYY-MM-DD_HH-MM-SS-ffffff.mp4).
     *
     * @param  \SplFileInfo[]  $files
     * @return \SplFileInfo[]
     */
    private function matchFilesByFilename(array $files, Recording $recording): array
    {
        if (! $recording->started_at) {
            return array_values($files);
        }

        $windowStart = $recording->started_at->timestamp - 120;
        $windowEnd = $recording->ended_at
            ? $recording->ended_at->timestamp + 180
            : time() + 180;

        $matched = [];
        foreach ($files as $file) {
            // Parse: 2026-02-09_17-32-57-529020.mp4
            if (preg_match('/^(\d{4}-\d{2}-\d{2})_(\d{2})-(\d{2})-(\d{2})/', $file->getFilename(), $m)) {
                $dateStr = "{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}";
                try {
                    // Try UTC first (MediaMTX default), then local timezone
                    $fileTime = \Carbon\Carbon::parse($dateStr, 'UTC')->timestamp;
                    if ($fileTime >= $windowStart && $fileTime <= $windowEnd) {
                        $matched[] = $file;
                        continue;
                    }
                    // Try with app timezone
                    $fileTimeLocal = \Carbon\Carbon::parse($dateStr, config('app.timezone'))->timestamp;
                    if ($fileTimeLocal >= $windowStart && $fileTimeLocal <= $windowEnd) {
                        $matched[] = $file;
                    }
                } catch (\Throwable) {
                    // Skip unparseable filenames
                }
            }
        }

        usort($matched, fn ($a, $b) => strcmp($a->getFilename(), $b->getFilename()));

        return $matched;
    }

    /**
     * Find the main MP4 file for a recording.
     *
     * 1. Try local filesystem first (file may still exist or be actively recording)
     * 2. If not found locally and recording has a storage_backend_id, download from remote backend
     */
    private function findRecordingFile(Recording $recording): ?string
    {
        // 1. Try local files first
        $localFiles = $this->getRecordingFiles($recording);

        if (! empty($localFiles)) {
            // Return the largest file (most data = main segment)
            $largest = null;
            $largestSize = 0;
            foreach ($localFiles as $file) {
                if ($file->getSize() > $largestSize) {
                    $largest = $file;
                    $largestSize = $file->getSize();
                }
            }

            return $largest?->getPathname();
        }

        // 2. Try remote storage backend
        return $this->findRecordingFileFromBackend($recording);
    }

    /**
     * Download and cache recording file from remote storage backend.
     */
    private function findRecordingFileFromBackend(Recording $recording): ?string
    {
        if (! $recording->storage_backend_id) {
            return null;
        }

        $backend = StorageBackend::find($recording->storage_backend_id);
        if (! $backend || $backend->driver === StorageBackend::DRIVER_LOCAL) {
            return null;
        }

        try {
            $driver = $this->storageManager->getDriver($backend);

            // Build the expected remote path
            $camera = $recording->camera;
            $cameraSlug = $camera?->slug ?? $camera?->id ?? 'unknown';
            $date = $recording->started_at?->format('Y/m/d') ?? 'unknown';
            $tenantId = $recording->tenant_id ?? 'global';
            $remoteDir = "recordings/tenant_{$tenantId}/{$cameraSlug}/{$date}";

            // List files in the remote directory
            $remoteFiles = $driver->listFiles($remoteDir);

            if (empty($remoteFiles)) {
                Log::debug('Nenhum arquivo encontrado no backend remoto.', [
                    'recording_id' => $recording->id,
                    'remote_dir' => $remoteDir,
                    'backend' => $backend->name,
                ]);

                return null;
            }

            // Filter to .mp4 files only
            $mp4Files = array_filter($remoteFiles, fn ($f) => str_ends_with(strtolower($f), '.mp4'));

            if (empty($mp4Files)) {
                return null;
            }

            // Find largest file by remote size
            $bestFile = null;
            $bestSize = 0;
            foreach ($mp4Files as $remotePath) {
                $size = $driver->fileSize($remotePath);
                if ($size > $bestSize) {
                    $bestFile = $remotePath;
                    $bestSize = $size;
                }
            }

            if (! $bestFile) {
                return null;
            }

            // Download to local cache
            $cacheDir = storage_path('app/recording_cache/' . $recording->id);
            if (! File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }

            $localFile = $cacheDir . '/' . basename($bestFile);

            // Skip download if already cached and size matches
            if (File::exists($localFile) && File::size($localFile) === $bestSize) {
                return $localFile;
            }

            // Stream download from remote backend
            $contents = $driver->retrieve($bestFile);
            File::put($localFile, $contents);

            Log::info('Arquivo de gravacao baixado do backend remoto.', [
                'recording_id' => $recording->id,
                'backend' => $backend->name,
                'remote_path' => $bestFile,
                'local_cache' => $localFile,
                'size' => $bestSize,
            ]);

            return $localFile;
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar gravacao do backend remoto.', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get segment info for a recording (only segments that belong to this recording).
     * Checks both local and remote storage.
     */
    private function getRecordingSegments(Recording $recording): array
    {
        // 1. Try local files
        $files = $this->getRecordingFiles($recording);

        if (! empty($files)) {
            $segments = [];
            foreach ($files as $file) {
                $segments[] = [
                    'filename' => $file->getFilename(),
                    'size_bytes' => $file->getSize(),
                    'size_human' => $this->formatFileSize($file->getSize()),
                    'source' => 'local',
                ];
            }

            return $segments;
        }

        // 2. Try remote backend
        if (! $recording->storage_backend_id) {
            return [];
        }

        $backend = StorageBackend::find($recording->storage_backend_id);
        if (! $backend || $backend->driver === StorageBackend::DRIVER_LOCAL) {
            return [];
        }

        try {
            $driver = $this->storageManager->getDriver($backend);

            $camera = $recording->camera;
            $cameraSlug = $camera?->slug ?? $camera?->id ?? 'unknown';
            $date = $recording->started_at?->format('Y/m/d') ?? 'unknown';
            $tenantId = $recording->tenant_id ?? 'global';
            $remoteDir = "recordings/tenant_{$tenantId}/{$cameraSlug}/{$date}";

            $remoteFiles = $driver->listFiles($remoteDir);
            $mp4Files = array_filter($remoteFiles, fn ($f) => str_ends_with(strtolower($f), '.mp4'));

            $segments = [];
            foreach ($mp4Files as $remotePath) {
                $size = $driver->fileSize($remotePath);
                $segments[] = [
                    'filename' => basename($remotePath),
                    'size_bytes' => $size,
                    'size_human' => $this->formatFileSize($size),
                    'source' => $backend->driver,
                ];
            }

            return $segments;
        } catch (\Throwable $e) {
            Log::warning('Erro ao listar segmentos do backend remoto.', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }

    private function authorizeRecording(Recording $recording): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $recording->tenant_id !== $user->tenant_id) {
            abort(403, 'Acesso negado.');
        }
    }
}
