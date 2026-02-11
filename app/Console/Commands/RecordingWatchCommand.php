<?php

namespace App\Console\Commands;

use App\Services\Recording\SegmentWatcherService;
use Illuminate\Console\Command;

class RecordingWatchCommand extends Command
{
    protected $signature = 'recording:watch
        {--once : Run once and exit (for scheduler)}
        {--interval=30 : Polling interval in seconds (for continuous mode)}';

    protected $description = 'Monitor recording directories for new segments and upload to storage backend';

    public function handle(SegmentWatcherService $watcher): int
    {
        if ($this->option('once')) {
            return $this->runOnce($watcher);
        }

        return $this->runContinuously($watcher);
    }

    private function runOnce(SegmentWatcherService $watcher): int
    {
        $result = $watcher->scan();

        if ($result['new_segments'] > 0 || $result['uploaded'] > 0) {
            $this->info("Scan: {$result['scanned']} dirs, {$result['new_segments']} new segments, {$result['uploaded']} uploaded");
        }

        return self::SUCCESS;
    }

    private function runContinuously(SegmentWatcherService $watcher): int
    {
        $interval = (int) $this->option('interval');
        $this->info("Recording watcher started (interval: {$interval}s). Press Ctrl+C to stop.");

        while (true) {
            try {
                $result = $watcher->scan();

                if ($result['new_segments'] > 0 || $result['uploaded'] > 0) {
                    $this->line(
                        now()->format('H:i:s') .
                        " | dirs={$result['scanned']}" .
                        " | new={$result['new_segments']}" .
                        " | uploaded={$result['uploaded']}"
                    );
                }
            } catch (\Throwable $e) {
                $this->error("Error: {$e->getMessage()}");
            }

            sleep($interval);
        }

        return self::SUCCESS; // @phpstan-ignore-line
    }
}
