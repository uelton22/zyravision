<?php

use App\Jobs\CaptureSnapshotJob;
use App\Jobs\CheckStorageBackendHealthJob;
use App\Jobs\CleanupExpiredRecordingsJob;
use App\Jobs\MonitorStreamsJob;
use App\Jobs\ScanRecordingSegmentsJob;
use App\Jobs\UpdateStorageQuotaJob;
use App\Models\Stream;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Streaming Scheduler ─────────────────────────────────

Schedule::job(new MonitorStreamsJob)->everyThirtySeconds();

Schedule::call(function () {
    $activeStreams = Stream::active()->pluck('camera_id');
    foreach ($activeStreams as $cameraId) {
        CaptureSnapshotJob::dispatch($cameraId);
    }
})->everyFiveMinutes()->name('capture-snapshots');

// ─── Recording Scheduler ─────────────────────────────────

// Segment Watcher: detecta novos segmentos e faz upload ao storage backend
// Roda a cada 30 segundos para detecao quase em tempo real
Schedule::command('recording:watch --once')->everyThirtySeconds()->name('segment-watcher');

Schedule::job(new ScanRecordingSegmentsJob)->everyMinute()->name('scan-recording-segments');
Schedule::job(new CleanupExpiredRecordingsJob)->dailyAt('03:00')->name('cleanup-expired-recordings');
Schedule::job(new \App\Jobs\CleanupExpiredSegmentsJob)->dailyAt('03:30')->name('cleanup-expired-segments');
Schedule::job(new UpdateStorageQuotaJob)->everyFifteenMinutes()->name('update-storage-quotas');

// ─── Storage Backend Health Checks ──────────────────────

Schedule::job(new CheckStorageBackendHealthJob)->everyFiveMinutes()->name('check-storage-backend-health');
