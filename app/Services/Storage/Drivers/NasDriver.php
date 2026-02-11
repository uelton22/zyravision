<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;

/**
 * NAS Generic Storage Driver.
 * Delegates to NFS or CIFS driver based on the configured protocol.
 */
class NasDriver extends AbstractDriver
{
    private ?AbstractDriver $innerDriver = null;

    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_NAS;
    }

    public function connect(): bool
    {
        return $this->getInnerDriver()->connect();
    }

    public function store(string $path, string $contents): bool
    {
        return $this->getInnerDriver()->store($path, $contents);
    }

    public function storeFile(string $path, string $localFilePath): bool
    {
        return $this->getInnerDriver()->storeFile($path, $localFilePath);
    }

    public function retrieve(string $path): string
    {
        return $this->getInnerDriver()->retrieve($path);
    }

    public function delete(string $path): bool
    {
        return $this->getInnerDriver()->delete($path);
    }

    public function deleteDirectory(string $directory): bool
    {
        return $this->getInnerDriver()->deleteDirectory($directory);
    }

    public function exists(string $path): bool
    {
        return $this->getInnerDriver()->exists($path);
    }

    public function listFiles(string $directory): array
    {
        return $this->getInnerDriver()->listFiles($directory);
    }

    public function fileSize(string $path): int
    {
        return $this->getInnerDriver()->fileSize($path);
    }

    public function getAbsolutePath(string $path): ?string
    {
        return $this->getInnerDriver()->getAbsolutePath($path);
    }

    public function getCapacity(): array
    {
        return $this->getInnerDriver()->getCapacity();
    }

    public function healthCheck(): array
    {
        return $this->getInnerDriver()->healthCheck();
    }

    private function getInnerDriver(): AbstractDriver
    {
        if ($this->innerDriver) {
            return $this->innerDriver;
        }

        $protocol = $this->getConfig('protocol', 'nfs');

        $mappedConfig = match ($protocol) {
            'nfs' => [
                'server' => $this->getConfig('server'),
                'port' => $this->getConfig('port', 2049),
                'export_path' => $this->getConfig('share_path'),
                'mount_point' => $this->getConfig('mount_point', '/mnt/nas_' . md5($this->getConfig('server', '') . $this->getConfig('share_path', ''))),
                'username' => $this->getConfig('username'),
                'password' => $this->getConfig('password'),
            ],
            'cifs' => [
                'server' => $this->getConfig('server'),
                'port' => $this->getConfig('port', 445),
                'share_name' => basename($this->getConfig('share_path', 'share')),
                'mount_point' => $this->getConfig('mount_point', '/mnt/nas_' . md5($this->getConfig('server', '') . $this->getConfig('share_path', ''))),
                'username' => $this->getConfig('username', 'guest'),
                'password' => $this->getConfig('password', ''),
            ],
            default => $this->config,
        };

        $this->innerDriver = match ($protocol) {
            'nfs' => new NfsDriver($mappedConfig),
            'cifs' => new CifsDriver($mappedConfig),
            default => new LocalDriver(array_merge($this->config, ['base_directory' => $this->getConfig('mount_point', '/mnt/nas')])),
        };

        return $this->innerDriver;
    }
}
