<?php

namespace App\Services\Storage\Drivers;

use App\Contracts\StorageDriverInterface;
use App\Models\StorageBackend;

/**
 * Custom Storage Driver.
 * Delegates to a user-provided driver class that implements StorageDriverInterface.
 */
class CustomDriver extends AbstractDriver
{
    private ?StorageDriverInterface $innerDriver = null;

    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_CUSTOM;
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

    private function getInnerDriver(): StorageDriverInterface
    {
        if ($this->innerDriver) {
            return $this->innerDriver;
        }

        $driverClass = $this->getConfig('driver_class');
        if (! $driverClass) {
            throw new \RuntimeException('driver_class nao configurado para driver personalizado');
        }

        if (! class_exists($driverClass)) {
            throw new \RuntimeException("Classe de driver nao encontrada: {$driverClass}");
        }

        if (! is_subclass_of($driverClass, StorageDriverInterface::class)) {
            throw new \RuntimeException("Classe {$driverClass} nao implementa StorageDriverInterface");
        }

        // Parse custom_config if it's a JSON string
        $customConfig = $this->getConfig('custom_config', []);
        if (is_string($customConfig)) {
            $customConfig = json_decode($customConfig, true) ?? [];
        }

        $this->innerDriver = new $driverClass($customConfig);

        return $this->innerDriver;
    }
}
