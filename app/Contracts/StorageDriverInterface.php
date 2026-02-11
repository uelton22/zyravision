<?php

namespace App\Contracts;

/**
 * Interface for all storage backend drivers.
 *
 * Each driver must implement these methods to provide a unified
 * abstraction layer for different storage backends (local, NAS, cloud, etc.).
 */
interface StorageDriverInterface
{
    /**
     * Test connectivity to the storage backend.
     *
     * @return bool True if connection is successful.
     */
    public function connect(): bool;

    /**
     * Store a file at the given path.
     *
     * @param  string  $path  Relative path within the backend.
     * @param  string  $contents  File contents to store.
     * @return bool True if stored successfully.
     */
    public function store(string $path, string $contents): bool;

    /**
     * Store a file from a local filesystem path (streaming upload).
     *
     * @param  string  $path  Relative path within the backend.
     * @param  string  $localFilePath  Absolute path to the local file.
     * @return bool True if stored successfully.
     */
    public function storeFile(string $path, string $localFilePath): bool;

    /**
     * Retrieve file contents from the given path.
     *
     * @param  string  $path  Relative path within the backend.
     * @return string File contents.
     *
     * @throws \RuntimeException If file not found or read error.
     */
    public function retrieve(string $path): string;

    /**
     * Delete a file at the given path.
     *
     * @param  string  $path  Relative path within the backend.
     * @return bool True if deleted (or already gone).
     */
    public function delete(string $path): bool;

    /**
     * Delete a directory and all its contents.
     *
     * @param  string  $directory  Relative directory path.
     * @return bool True if deleted successfully.
     */
    public function deleteDirectory(string $directory): bool;

    /**
     * Check if a file exists at the given path.
     *
     * @param  string  $path  Relative path within the backend.
     * @return bool True if the file exists.
     */
    public function exists(string $path): bool;

    /**
     * List files in a directory.
     *
     * @param  string  $directory  Relative directory path.
     * @return array<string> Array of relative file paths.
     */
    public function listFiles(string $directory): array;

    /**
     * Get the file size in bytes.
     *
     * @param  string  $path  Relative path within the backend.
     * @return int File size in bytes, or 0 if not found.
     */
    public function fileSize(string $path): int;

    /**
     * Get the full/absolute path for a relative file path.
     * Useful for local drivers where direct file access is possible.
     *
     * @param  string  $path  Relative path within the backend.
     * @return string|null Absolute path, or null if not applicable (cloud).
     */
    public function getAbsolutePath(string $path): ?string;

    /**
     * Get storage capacity information.
     *
     * @return array{total_gb: float|null, used_gb: float|null, free_gb: float|null}
     */
    public function getCapacity(): array;

    /**
     * Run a health check on the storage backend.
     *
     * @return array{status: string, latency_ms: int, message: string}
     *   status: 'healthy', 'degraded', 'offline'
     */
    public function healthCheck(): array;

    /**
     * Get the driver type identifier.
     *
     * @return string One of the StorageBackend::DRIVER_* constants.
     */
    public function getDriverType(): string;
}
