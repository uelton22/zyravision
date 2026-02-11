<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * CIFS/SMB Storage Driver.
 * Uses mounted CIFS shares for Windows/Samba network storage.
 */
class CifsDriver extends LocalDriver
{
    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_CIFS;
    }

    public function connect(): bool
    {
        $mountPoint = $this->getConfig('mount_point');
        $server = $this->getConfig('server');
        $shareName = $this->getConfig('share_name');

        if (! $mountPoint || ! $server || ! $shareName) {
            return false;
        }

        if ($this->isMounted($mountPoint)) {
            $this->config['base_directory'] = $mountPoint;

            return parent::connect();
        }

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

        $measurement = $this->measureLatency(function () use ($server, $mountPoint) {
            $result = Process::timeout(5)->run("ping -c 1 -W 2 " . escapeshellarg($server));
            if (! $result->successful()) {
                throw new \RuntimeException("Servidor CIFS {$server} inacessivel");
            }

            if (! $this->isMounted($mountPoint)) {
                throw new \RuntimeException("CIFS nao esta montado em {$mountPoint}");
            }

            $testFile = $mountPoint . '/.health_check_' . uniqid();
            File::put($testFile, 'cifs_health');
            File::delete($testFile);

            return true;
        });

        if ($measurement['result'] === false) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => $measurement['latency_ms'],
                'message' => $measurement['error'] ?? 'Falha na verificacao CIFS',
            ];
        }

        $status = $measurement['latency_ms'] > 500
            ? StorageBackend::HEALTH_DEGRADED
            : StorageBackend::HEALTH_HEALTHY;

        return [
            'status' => $status,
            'latency_ms' => $measurement['latency_ms'],
            'message' => $status === StorageBackend::HEALTH_DEGRADED
                ? "CIFS respondendo com alta latencia ({$measurement['latency_ms']}ms)"
                : 'CIFS/SMB operando normalmente',
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
        $shareName = $this->getConfig('share_name');
        $port = $this->getConfig('port', 445);
        $username = $this->getConfig('username', 'guest');
        $password = $this->getConfig('password', '');
        $domain = $this->getConfig('domain', 'WORKGROUP');
        $smbVersion = $this->getConfig('smb_version', '3.0');
        $extraOptions = $this->getConfig('mount_options', '');

        if (! File::isDirectory($mountPoint)) {
            File::makeDirectory($mountPoint, 0755, true);
        }

        $source = "//{$server}/{$shareName}";
        $options = "port={$port},username={$username},password={$password},domain={$domain},vers={$smbVersion}";
        if ($extraOptions) {
            $options .= ",{$extraOptions}";
        }

        $cmd = "mount -t cifs -o " . escapeshellarg($options) . " " . escapeshellarg($source) . " " . escapeshellarg($mountPoint);

        try {
            $result = Process::timeout(30)->run($cmd);

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
