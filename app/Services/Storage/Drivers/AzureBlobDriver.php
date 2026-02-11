<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * Azure Blob Storage Driver.
 * Uses Laravel's Azure Blob filesystem adapter.
 */
class AzureBlobDriver extends AbstractDriver
{
    private ?FilesystemAdapter $disk = null;

    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_AZURE_BLOB;
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
            return $this->getDisk()->put($this->prefixPath($path), $contents);
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
            $result = $this->getDisk()->writeStream($this->prefixPath($path), $stream);
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
            throw new \RuntimeException("Arquivo nao encontrado no Azure Blob: {$path}");
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
        return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
    }

    public function healthCheck(): array
    {
        $measurement = $this->measureLatency(function () {
            $disk = $this->getDisk();
            $prefix = $this->getConfig('prefix', '');
            $testPath = $prefix . '.health_check_' . uniqid();
            $disk->put($testPath, 'azure_health');
            $disk->delete($testPath);

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na conexao com Azure Blob',
            ];
        }

        return [
            'status' => $measurement['latency_ms'] > 5000 ? StorageBackend::HEALTH_DEGRADED : StorageBackend::HEALTH_HEALTHY,
            'latency_ms' => $measurement['latency_ms'],
            'message' => 'Azure Blob Storage operando normalmente',
        ];
    }

    private function getDisk(): FilesystemAdapter
    {
        if ($this->disk) {
            return $this->disk;
        }

        $connectionString = $this->getConfig('connection_string');

        if (! $connectionString) {
            $accountName = $this->getConfig('account_name');
            $accountKey = $this->getConfig('account_key');
            $connectionString = "DefaultEndpointsProtocol=https;AccountName={$accountName};AccountKey={$accountKey}";
        }

        // Uses MicrosoftAzure/storage-blob adapter
        $this->disk = Storage::build([
            'driver' => 'azure',
            'connection_string' => $connectionString,
            'container' => $this->getConfig('container'),
        ]);

        return $this->disk;
    }

    private function prefixPath(string $path): string
    {
        $prefix = trim($this->getConfig('prefix', ''), '/');

        return $prefix ? $prefix . '/' . ltrim($path, '/') : $path;
    }
}
