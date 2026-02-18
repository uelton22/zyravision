<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSegmentThumbnailJob;
use App\Models\RecordingSegment;
use Illuminate\Console\Command;

class GenerateMissingThumbnailsCommand extends Command
{
    protected $signature = 'segments:generate-thumbnails {--limit=100 : Max segments to process}';

    protected $description = 'Generate thumbnails for segments that do not have one yet';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $segments = RecordingSegment::query()
            ->available()
            ->whereNull('thumbnail_path')
            ->whereNotNull('local_path')
            ->orderByDesc('starts_at')
            ->limit($limit)
            ->get();

        $this->info("Found {$segments->count()} segments without thumbnails.");

        $dispatched = 0;
        foreach ($segments as $segment) {
            GenerateSegmentThumbnailJob::dispatch($segment->id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} thumbnail generation jobs.");

        return self::SUCCESS;
    }
}
