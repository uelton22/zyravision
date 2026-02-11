<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * SFTP Storage Driver.
 * Uses Laravel's SFTP filesystem adapter (league/flysystem-sftp-v3).
 */
class SftpDriver extends AbstractDriver
{
    private ?FilesystemAdapter $disk = null;

    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_SFTP;
    }

    public function connect(): bool
    {
        try {
            $disk = $this->getDisk();
            // Test by listing root
            $disk->files('/');

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
            throw new \RuntimeException("Arquivo nao encontrado via SFTP: {$path}");
        }

        return $contents;
    }

    public function delete(string $path): bool
    {
        try {
            return $this->getDisk()->delete($path);
        } catch (\Throwable) {
            return true; // Already gone
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
        // SFTP doesn't provide capacity info via standard API
        return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
    }

    public function healthCheck(): array
    {
        $measurement = $this->measureLatency(function () {
            $disk = $this->getDisk();

            // Test write/read/delete
            $testPath = '.health_check_' . uniqid();
            $disk->put($testPath, 'sftp_health');

            $content = $disk->get($testPath);
            if ($content !== 'sftp_health') {
                throw new \RuntimeException('Falha na verificacao de leitura/escrita SFTP');
            }

            $disk->delete($testPath);

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na conexao SFTP',
            ];
        }

        $status = $measurement['latency_ms'] > 2000
            ? StorageBackend::HEALTH_DEGRADED
            : StorageBackend::HEALTH_HEALTHY;

        return [
            'status' => $status,
            'latency_ms' => $measurement['latency_ms'],
            'message' => $status === StorageBackend::HEALTH_DEGRADED
                ? "SFTP respondendo com alta latencia ({$measurement['latency_ms']}ms)"
                : 'SFTP operando normalmente',
        ];
    }

    private function getDisk(): FilesystemAdapter
    {
        if ($this->disk) {
            return $this->disk;
        }

        $config = [
            'driver' => 'sftp',
            'host' => $this->getConfig('server'),
            'port' => (int) $this->getConfig('port', 22),
            'root' => $this->getConfig('base_directory', '/'),
            'username' => $this->getConfig('username'),
            'timeout' => 30,
        ];

        if ($this->getConfig('private_key')) {
            $config['privateKey'] = $this->getConfig('private_key');
            if ($this->getConfig('key_passphrase')) {
                $config['passphrase'] = $this->getConfig('key_passphrase');
            }
        } else {
            $config['password'] = $this->getConfig('password');
        }

        if ($this->getConfig('host_fingerprint')) {
            $config['hostFingerprint'] = $this->getConfig('host_fingerprint');
        }

        $this->disk = Storage::build($config);

        return $this->disk;
    }
}
