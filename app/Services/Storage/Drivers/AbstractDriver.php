<?php

namespace App\Services\Storage\Drivers;

use App\Contracts\StorageDriverInterface;

abstract class AbstractDriver implements StorageDriverInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get a config value with an optional default.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Measure the latency of a callable in milliseconds.
     */
    protected function measureLatency(callable $fn): array
    {
        $start = hrtime(true);

        try {
            $result = $fn();
            $latencyMs = (int) ((hrtime(true) - $start) / 1_000_000);

            return ['result' => $result, 'latency_ms' => $latencyMs];
        } catch (\Throwable $e) {
            $latencyMs = (int) ((hrtime(true) - $start) / 1_000_000);

            return ['result' => false, 'latency_ms' => $latencyMs, 'error' => $e->getMessage()];
        }
    }

    /**
     * Default storeFile implementation: reads file and calls store().
     */
    public function storeFile(string $path, string $localFilePath): bool
    {
        if (! file_exists($localFilePath)) {
            return false;
        }

        $contents = file_get_contents($localFilePath);
        if ($contents === false) {
            return false;
        }

        return $this->store($path, $contents);
    }

    /**
     * Default fileSize implementation.
     */
    public function fileSize(string $path): int
    {
        try {
            return strlen($this->retrieve($path));
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Default getAbsolutePath — null for non-local drivers.
     */
    public function getAbsolutePath(string $path): ?string
    {
        return null;
    }
}
