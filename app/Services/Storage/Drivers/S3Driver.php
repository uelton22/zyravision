<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * AWS S3 Storage Driver.
 * Uses Laravel's S3 filesystem adapter (league/flysystem-aws-s3-v3).
 */
class S3Driver extends AbstractDriver
{
    private ?FilesystemAdapter $disk = null;

    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_S3;
    }

    public function connect(): bool
    {
        try {
            $this->getDisk()->files($this->getConfig('prefix', ''));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function store(string $path, string $contents): bool
    {
        try {
            $options = $this->getStorageOptions();

            return $this->getDisk()->put($this->prefixPath($path), $contents, $options);
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

            $result = $this->getDisk()->writeStream($this->prefixPath($path), $stream, $this->getStorageOptions());
            fclose($stream);

            return $result;
        } catch (\Throwable) {
            return false;
        }
    }

    public function retrieve(string $path): string
    {
        $contents = $this->getDisk()->get($this->prefixPath($path));
        if ($contents === null) {
            throw new \RuntimeException("Arquivo nao encontrado no S3: {$path}");
        }

        return $contents;
    }

    public function delete(string $path): bool
    {
        try {
            return $this->getDisk()->delete($this->prefixPath($path));
        } catch (\Throwable) {
            return true;
        }
    }

    public function deleteDirectory(string $directory): bool
    {
        try {
            return $this->getDisk()->deleteDirectory($this->prefixPath($directory));
        } catch (\Throwable) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return $this->getDisk()->exists($this->prefixPath($path));
        } catch (\Throwable) {
            return false;
        }
    }

    public function listFiles(string $directory): array
    {
        try {
            return $this->getDisk()->allFiles($this->prefixPath($directory));
        } catch (\Throwable) {
            return [];
        }
    }

    public function fileSize(string $path): int
    {
        try {
            return $this->getDisk()->size($this->prefixPath($path));
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getCapacity(): array
    {
        // S3 has unlimited capacity
        return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
    }

    public function healthCheck(): array
    {
        $measurement = $this->measureLatency(function () {
            $disk = $this->getDisk();
            $prefix = $this->getConfig('prefix', '');
            $testPath = $prefix . '.health_check_' . uniqid();
            $disk->put($testPath, 's3_health');
            $content = $disk->get($testPath);
            $disk->delete($testPath);

            if ($content !== 's3_health') {
                throw new \RuntimeException('Falha na verificacao de leitura/escrita S3');
            }

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na conexao com AWS S3',
            ];
        }

        return [
            'status' => $measurement['latency_ms'] > 5000 ? StorageBackend::HEALTH_DEGRADED : StorageBackend::HEALTH_HEALTHY,
            'latency_ms' => $measurement['latency_ms'],
            'message' => 'AWS S3 operando normalmente',
        ];
    }

    private function getDisk(): FilesystemAdapter
    {
        if ($this->disk) {
            return $this->disk;
        }

        $config = [
            'driver' => 's3',
            'key' => $this->getConfig('access_key_id'),
            'secret' => $this->getConfig('secret_access_key'),
            'region' => $this->getConfig('region', 'us-east-1'),
            'bucket' => $this->getConfig('bucket'),
        ];

        if ($this->getConfig('session_token')) {
            $config['token'] = $this->getConfig('session_token');
        }

        $this->disk = Storage::build($config);

        return $this->disk;
    }

    private function prefixPath(string $path): string
    {
        $prefix = trim($this->getConfig('prefix', ''), '/');

        return $prefix ? $prefix . '/' . ltrim($path, '/') : $path;
    }

    private function getStorageOptions(): array
    {
        $options = [];

        $storageClass = $this->getConfig('storage_class');
        if ($storageClass && $storageClass !== 'STANDARD') {
            $options['StorageClass'] = $storageClass;
        }

        $encryption = $this->getConfig('encryption');
        if ($encryption && $encryption !== 'none') {
            $options['ServerSideEncryption'] = $encryption;
            if ($encryption === 'aws:kms' && $this->getConfig('kms_key_id')) {
                $options['SSEKMSKeyId'] = $this->getConfig('kms_key_id');
            }
        }

        return $options;
    }
}
