<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Support\Facades\File;

class LocalDriver extends AbstractDriver
{
    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_LOCAL;
    }

    public function connect(): bool
    {
        $baseDir = $this->getConfig('base_directory');

        if (! $baseDir) {
            return false;
        }

        // Ensure directory exists, create if needed
        if (! File::isDirectory($baseDir)) {
            File::makeDirectory($baseDir, 0755, true);
        }

        return File::isDirectory($baseDir) && is_writable($baseDir);
    }

    public function store(string $path, string $contents): bool
    {
        $fullPath = $this->resolvePath($path);
        $dir = dirname($fullPath);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return File::put($fullPath, $contents) !== false;
    }

    public function storeFile(string $path, string $localFilePath): bool
    {
        if (! file_exists($localFilePath)) {
            return false;
        }

        $fullPath = $this->resolvePath($path);
        $dir = dirname($fullPath);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return File::copy($localFilePath, $fullPath);
    }

    public function retrieve(string $path): string
    {
        $fullPath = $this->resolvePath($path);

        if (! File::exists($fullPath)) {
            throw new \RuntimeException("Arquivo nao encontrado: {$path}");
        }

        return File::get($fullPath);
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->resolvePath($path);

        if (! File::exists($fullPath)) {
            return true;
        }

        return File::delete($fullPath);
    }

    public function deleteDirectory(string $directory): bool
    {
        $fullPath = $this->resolvePath($directory);

        if (! File::isDirectory($fullPath)) {
            return true;
        }

        return File::deleteDirectory($fullPath);
    }

    public function exists(string $path): bool
    {
        return File::exists($this->resolvePath($path));
    }

    public function listFiles(string $directory): array
    {
        $fullPath = $this->resolvePath($directory);

        if (! File::isDirectory($fullPath)) {
            return [];
        }

        $files = File::allFiles($fullPath);
        $baseDir = rtrim($this->getConfig('base_directory'), '/');

        return array_map(function ($file) use ($baseDir) {
            return str_replace($baseDir . '/', '', $file->getPathname());
        }, $files);
    }

    public function fileSize(string $path): int
    {
        $fullPath = $this->resolvePath($path);

        return File::exists($fullPath) ? File::size($fullPath) : 0;
    }

    public function getAbsolutePath(string $path): ?string
    {
        return $this->resolvePath($path);
    }

    public function getCapacity(): array
    {
        $baseDir = $this->getConfig('base_directory');

        if (! $baseDir || ! File::isDirectory($baseDir)) {
            return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
        }

        $maxCapacityGb = $this->getConfig('max_capacity_gb');
        $totalBytes = @disk_total_space($baseDir);
        $freeBytes = @disk_free_space($baseDir);

        if ($totalBytes === false || $freeBytes === false) {
            return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
        }

        $totalGb = round($totalBytes / (1024 ** 3), 2);
        $freeGb = round($freeBytes / (1024 ** 3), 2);
        $usedGb = round(($totalBytes - $freeBytes) / (1024 ** 3), 2);

        // If max_capacity_gb is set, use it as total
        if ($maxCapacityGb && $maxCapacityGb > 0) {
            $totalGb = (float) $maxCapacityGb;
            // Recalculate free based on limit
            $freeGb = max(0, $totalGb - $usedGb);
        }

        return [
            'total_gb' => $totalGb,
            'used_gb' => $usedGb,
            'free_gb' => $freeGb,
        ];
    }

    public function healthCheck(): array
    {
        $measurement = $this->measureLatency(function () {
            $baseDir = $this->getConfig('base_directory');
            if (! $baseDir) {
                throw new \RuntimeException('base_directory nao configurado');
            }

            if (! File::isDirectory($baseDir)) {
                throw new \RuntimeException("Diretorio nao existe: {$baseDir}");
            }

            if (! is_writable($baseDir)) {
                throw new \RuntimeException("Diretorio nao tem permissao de escrita: {$baseDir}");
            }

            // Test write operation
            $testFile = $baseDir . '/.health_check_' . uniqid();
            File::put($testFile, 'health_check');
            File::delete($testFile);

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na verificacao de saude',
            ];
        }

        // Check disk space
        $capacity = $this->getCapacity();
        $usagePercent = ($capacity['total_gb'] && $capacity['total_gb'] > 0)
            ? ($capacity['used_gb'] / $capacity['total_gb']) * 100
            : 0;

        $status = StorageBackend::HEALTH_HEALTHY;
        $message = 'Armazenamento local operando normalmente';

        if ($usagePercent > 95) {
            $status = StorageBackend::HEALTH_DEGRADED;
            $message = "Espaco em disco critico: {$usagePercent}% utilizado";
        } elseif ($usagePercent > 85) {
            $status = StorageBackend::HEALTH_DEGRADED;
            $message = "Espaco em disco alto: {$usagePercent}% utilizado";
        }

        return [
            'status' => $status,
            'latency_ms' => $measurement['latency_ms'],
            'message' => $message,
        ];
    }

    private function resolvePath(string $path): string
    {
        $baseDir = rtrim($this->getConfig('base_directory', '/recordings'), '/');

        return $baseDir . '/' . ltrim($path, '/');
    }
}
