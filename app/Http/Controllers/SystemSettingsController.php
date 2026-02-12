<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/System/Index', [
            'cacheInfo' => $this->getCacheInfo(),
        ]);
    }

    /**
     * Clear video segment cache (storage/app/segment_cache/).
     */
    public function clearSegmentCache()
    {
        $cacheBasePath = storage_path('app/segment_cache');
        $freedBytes = 0;
        $deletedFiles = 0;

        if (File::isDirectory($cacheBasePath)) {
            // Count before deleting
            $files = File::allFiles($cacheBasePath);
            foreach ($files as $file) {
                $freedBytes += $file->getSize();
                $deletedFiles++;
            }

            File::deleteDirectory($cacheBasePath);
            File::makeDirectory($cacheBasePath, 0755, true);
        }

        $freedFormatted = $this->formatBytes($freedBytes);

        return back()->with('success', "Cache de segmentos limpo: {$deletedFiles} arquivos removidos ({$freedFormatted} liberados).");
    }

    /**
     * Clear general application cache (config, routes, views, app cache).
     */
    public function clearAppCache()
    {
        $results = [];

        // Clear application cache store
        Artisan::call('cache:clear');
        $results[] = 'cache da aplicacao';

        // Clear compiled views
        Artisan::call('view:clear');
        $results[] = 'views compiladas';

        // Clear config cache
        Artisan::call('config:clear');
        $results[] = 'cache de configuracao';

        // Clear route cache
        Artisan::call('route:clear');
        $results[] = 'cache de rotas';

        // Clear event cache
        Artisan::call('event:clear');
        $results[] = 'cache de eventos';

        $summary = implode(', ', $results);

        return back()->with('success', "Cache geral limpo com sucesso: {$summary}.");
    }

    /**
     * Gather cache size info for the frontend.
     */
    private function getCacheInfo(): array
    {
        // Segment cache
        $segmentCachePath = storage_path('app/segment_cache');
        $segmentCacheSize = 0;
        $segmentCacheFiles = 0;

        if (File::isDirectory($segmentCachePath)) {
            $files = File::allFiles($segmentCachePath);
            foreach ($files as $file) {
                $segmentCacheSize += $file->getSize();
                $segmentCacheFiles++;
            }
        }

        // Views cache
        $viewsCachePath = storage_path('framework/views');
        $viewsCacheSize = 0;
        $viewsCacheFiles = 0;

        if (File::isDirectory($viewsCachePath)) {
            $files = File::allFiles($viewsCachePath);
            foreach ($files as $file) {
                $viewsCacheSize += $file->getSize();
                $viewsCacheFiles++;
            }
        }

        // App cache (file driver)
        $appCachePath = storage_path('framework/cache/data');
        $appCacheSize = 0;
        $appCacheFiles = 0;

        if (File::isDirectory($appCachePath)) {
            $files = File::allFiles($appCachePath);
            foreach ($files as $file) {
                $appCacheSize += $file->getSize();
                $appCacheFiles++;
            }
        }

        return [
            'segment_cache' => [
                'size' => $segmentCacheSize,
                'size_formatted' => $this->formatBytes($segmentCacheSize),
                'files' => $segmentCacheFiles,
            ],
            'views_cache' => [
                'size' => $viewsCacheSize,
                'size_formatted' => $this->formatBytes($viewsCacheSize),
                'files' => $viewsCacheFiles,
            ],
            'app_cache' => [
                'size' => $appCacheSize,
                'size_formatted' => $this->formatBytes($appCacheSize),
                'files' => $appCacheFiles,
            ],
            'total_size' => $segmentCacheSize + $viewsCacheSize + $appCacheSize,
            'total_size_formatted' => $this->formatBytes($segmentCacheSize + $viewsCacheSize + $appCacheSize),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }
}
