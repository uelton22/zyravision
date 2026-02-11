<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * FTP/FTPS Storage Driver.
 * Uses Laravel's FTP filesystem adapter.
 */
class FtpDriver extends AbstractDriver
{
    private ?FilesystemAdapter $disk = null;

    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_FTP;
    }

    public function connect(): bool
    {
        try {
            $this->getDisk()->files('/');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function store(string $path, string $contents): bool
    {
        try {
            return $this->getDisk()->put($path, $contents);
        } catch (\Throwable) {
            return false;
        }
    }

    public function storeFile(string $path, string $localFilePath): bool
    {
        try {
            $stream = fopen($localFilePath, 'rb');
            if (! $stream) {
                return false;
            }
            $result = $this->getDisk()->writeStream($path, $stream);
            fclose($stream);

            return $result;
        } catch (\Throwable) {
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        $contents = $this->getDisk()->get($path);
        if ($contents === null) {
            throw new \RuntimeException("Arquivo nao encontrado via FTP: {$path}");
        }

        return $contents;
    }

    public function delete(string $path): bool
    {
        try {
            return $this->getDisk()->delete($path);
        } catch (\Throwable) {
            return true;
        }
    }

    public function deleteDirectory(string $directory): bool
    {
        try {
            return $this->getDisk()->deleteDirectory($directory);
        } catch (\Throwable) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return $this->getDisk()->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    public function listFiles(string $directory): array
    {
        try {
            return $this->getDisk()->allFiles($directory);
        } catch (\Throwable) {
            return [];
        }
    }

    public function fileSize(string $path): int
    {
        try {
            return $this->getDisk()->size($path);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getCapacity(): array
    {
        return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
    }

    public function healthCheck(): array
    {
        $measurement = $this->measureLatency(function () {
            $disk = $this->getDisk();
            $testPath = '.health_check_' . uniqid();
            $disk->put($testPath, 'ftp_health');
            $disk->delete($testPath);

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na conexao FTP',
            ];
        }

        return [
            'status' => $measurement['latency_ms'] > 3000 ? StorageBackend::HEALTH_DEGRADED : StorageBackend::HEALTH_HEALTHY,
            'latency_ms' => $measurement['latency_ms'],
            'message' => 'FTP operando normalmente',
        ];
    }

    private function getDisk(): FilesystemAdapter
    {
        if ($this->disk) {
            return $this->disk;
        }

        $config = [
            'driver' => 'ftp',
            'host' => $this->getConfig('server'),
            'port' => (int) $this->getConfig('port', 21),
            'root' => $this->getConfig('base_directory', '/'),
            'username' => $this->getConfig('username'),
            'password' => $this->getConfig('password'),
            'ssl' => (bool) $this->getConfig('use_ssl', false),
            'passive' => (bool) $this->getConfig('passive_mode', true),
            'timeout' => 30,
        ];

        $this->disk = Storage::build($config);

        return $this->disk;
    }
}
