<?php

namespace App\Console\Commands;

use App\Models\RecordingSegment;
use Illuminate\Console\Command;

class FixSegmentTimestampsCommand extends Command
{
    protected $signature = 'segments:fix-timestamps {--dry-run : Only report, do not fix}';

    protected $description = 'Fix segments where ends_at is inconsistent with starts_at + duration_seconds';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fixed = 0;

        $segments = RecordingSegment::whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('status', RecordingSegment::STATUS_READY)
            ->where('duration_seconds', '>', 0)
            ->cursor();

        foreach ($segments as $segment) {
            $actualGap = (int) $segment->starts_at->diffInSeconds($segment->ends_at);
            $tolerance = max(120, $segment->duration_seconds * 0.5);

            if (abs($actualGap - $segment->duration_seconds) <= $tolerance) {
                continue;
            }

            $correctEndsAt = $segment->starts_at->copy()->addSeconds($segment->duration_seconds);

            if ($dryRun) {
                $this->warn("Would fix: {$segment->id} | ends_at: {$segment->ends_at} -> {$correctEndsAt} (gap={$actualGap}s, dur={$segment->duration_seconds}s)");
            } else {
                $segment->update(['ends_at' => $correctEndsAt]);
                $this->info("Fixed: {$segment->id} | ends_at -> {$correctEndsAt}");
            }

            $fixed++;
        }

        $action = $dryRun ? 'Would fix' : 'Fixed';
        $this->info("{$action} {$fixed} segment(s).");

        return self::SUCCESS;
    }
}
