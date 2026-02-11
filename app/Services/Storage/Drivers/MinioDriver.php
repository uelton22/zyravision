<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * MinIO Storage Driver.
 * S3-compatible object storage, uses the same S3 adapter with custom endpoint.
 */
class MinioDriver extends S3Driver
{
    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_MINIO;
    }

    public function healthCheck(): array
    {
        $measurement = $this->measureLatency(function () {
            $disk = $this->getMinioDisk();
            $testPath = '.health_check_' . uniqid();

            $disk->put($testPath, 'minio_health');
            $content = $disk->get($testPath);
            $disk->delete($testPath);

            if ($content !== 'minio_health') {
                throw new \RuntimeException('Falha na verificacao de leitura/escrita MinIO');
            }

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na conexao com MinIO',
            ];
        }

        return [
            'status' => $measurement['latency_ms'] > 5000 ? StorageBackend::HEALTH_DEGRADED : StorageBackend::HEALTH_HEALTHY,
            'latency_ms' => $measurement['latency_ms'],
            'message' => 'MinIO operando normalmente',
        ];
    }

    /**
     * Override to build MinIO-compatible S3 disk.
     */
    private ?FilesystemAdapter $minioDisk = null;

    protected function getMinioDisk(): FilesystemAdapter
    {
        if ($this->minioDisk) {
            return $this->minioDisk;
        }

        $endpoint = $this->getConfig('endpoint');
        $port = $this->getConfig('port');
        if ($port && ! str_contains($endpoint, ':' . $port)) {
            $parsedUrl = parse_url($endpoint);
            if (! isset($parsedUrl['port'])) {
                $scheme = $parsedUrl['scheme'] ?? 'http';
                $host = $parsedUrl['host'] ?? $endpoint;
                $endpoint = "{$scheme}://{$host}:{$port}";
            }
        }

        $this->minioDisk = Storage::build([
            'driver' => 's3',
            'key' => $this->getConfig('access_key'),
            'secret' => $this->getConfig('secret_key'),
            'region' => $this->getConfig('region', 'us-east-1'),
            'bucket' => $this->getConfig('bucket'),
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => (bool) $this->getConfig('path_style', true),
            'scheme' => (bool) $this->getConfig('use_ssl', false) ? 'https' : 'http',
        ]);

        return $this->minioDisk;
    }

    public function connect(): bool
    {
        try {
            $this->getMinioDisk()->files('');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function store(string $path, string $contents): bool
    {
        try {
            return $this->getMinioDisk()->put($path, $contents);
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
            $result = $this->getMinioDisk()->writeStream($path, $stream);
            fclose($stream);

            return $result;
        } catch (\Throwable) {
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        $contents = $this->getMinioDisk()->get($path);
        if ($contents === null) {
            throw new \RuntimeException("Arquivo nao encontrado no MinIO: {$path}");
        }

        return $contents;
    }

    public function delete(string $path): bool
    {
        try {
            return $this->getMinioDisk()->delete($path);
        } catch (\Throwable) {
            return true;
        }
    }

    public function deleteDirectory(string $directory): bool
    {
        try {
            return $this->getMinioDisk()->deleteDirectory($directory);
        } catch (\Throwable) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return $this->getMinioDisk()->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    public function listFiles(string $directory): array
    {
        try {
            return $this->getMinioDisk()->allFiles($directory);
        } catch (\Throwable) {
            return [];
        }
    }

    public function fileSize(string $path): int
    {
        try {
            return $this->getMinioDisk()->size($path);
        } catch (\Throwable) {
            return 0;
        }
    }
}
