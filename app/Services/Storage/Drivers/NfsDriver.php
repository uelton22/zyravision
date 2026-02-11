<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * NFS Storage Driver.
 * Uses mounted NFS shares. Auto-mounts if not already mounted.
 */
class NfsDriver extends LocalDriver
{
    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_NFS;
    }

    public function connect(): bool
    {
        $mountPoint = $this->getConfig('mount_point');
        $server = $this->getConfig('server');
        $exportPath = $this->getConfig('export_path');

        if (! $mountPoint || ! $server || ! $exportPath) {
            return false;
        }

        // Check if already mounted
        if ($this->isMounted($mountPoint)) {
            $this->config['base_directory'] = $mountPoint;

            return parent::connect();
        }

        // Try to mount
        if (! $this->mount()) {
            return false;
        }

        $this->config['base_directory'] = $mountPoint;

        return parent::connect();
    }

    public function healthCheck(): array
    {
        $mountPoint = $this->getConfig('mount_point');
        $server = $this->getConfig('server');
        $this->config['base_directory'] = $mountPoint;

        // Test server reachability
        $measurement = $this->measureLatency(function () use ($server, $mountPoint) {
            // Quick ping test
            $result = Process::timeout(5)->run("ping -c 1 -W 2 " . escapeshellarg($server));
            if (! $result->successful()) {
                throw new \RuntimeException("Servidor NFS {$server} inacessivel");
            }

            // Check mount
            if (! $this->isMounted($mountPoint)) {
                throw new \RuntimeException("NFS nao esta montado em {$mountPoint}");
            }

            // Test write
            $testFile = $mountPoint . '/.health_check_' . uniqid();
            File::put($testFile, 'nfs_health');
            File::delete($testFile);

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na verificacao NFS',
            ];
        }

        $status = $measurement['latency_ms'] > 500
            ? StorageBackend::HEALTH_DEGRADED
            : StorageBackend::HEALTH_HEALTHY;

        return [
            'status' => $status,
            'latency_ms' => $measurement['latency_ms'],
            'message' => $status === StorageBackend::HEALTH_DEGRADED
                ? "NFS respondendo com alta latencia ({$measurement['latency_ms']}ms)"
                : 'NFS operando normalmente',
        ];
    }

    private function isMounted(string $mountPoint): bool
    {
        if (! File::isDirectory($mountPoint)) {
            return false;
        }

        try {
            $result = Process::timeout(5)->run("mountpoint -q " . escapeshellarg($mountPoint));

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function mount(): bool
    {
        $mountPoint = $this->getConfig('mount_point');
        $server = $this->getConfig('server');
        $exportPath = $this->getConfig('export_path');
        $port = $this->getConfig('port', 2049);
        $nfsVersion = $this->getConfig('nfs_version', '4');
        $mountOptions = $this->getConfig('mount_options', 'rw,sync,hard,intr');

        if (! File::isDirectory($mountPoint)) {
            File::makeDirectory($mountPoint, 0755, true);
        }

        $source = "{$server}:{$exportPath}";
        $options = "port={$port},vers={$nfsVersion},{$mountOptions}";
        $cmd = "mount -t nfs -o " . escapeshellarg($options) . " " . escapeshellarg($source) . " " . escapeshellarg($mountPoint);

        try {
            $result = Process::timeout(30)->run($cmd);

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
