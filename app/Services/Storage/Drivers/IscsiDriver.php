<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * iSCSI Storage Driver.
 * Uses mounted iSCSI targets for block-level network storage.
 */
class IscsiDriver extends LocalDriver
{
    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_ISCSI;
    }

    public function connect(): bool
    {
        $mountPoint = $this->getConfig('mount_point');
        if (! $mountPoint) {
            return false;
        }

        if ($this->isMounted($mountPoint)) {
            $this->config['base_directory'] = $mountPoint;

            return parent::connect();
        }

        // Try to discover and login to iSCSI target
        if (! $this->loginTarget()) {
            return false;
        }

        $this->config['base_directory'] = $mountPoint;

        return parent::connect();
    }

    public function healthCheck(): array
    {
        $mountPoint = $this->getConfig('mount_point');
        $this->config['base_directory'] = $mountPoint;

        if (! $this->isMounted($mountPoint)) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => 0,
                'message' => "Volume iSCSI nao esta montado em {$mountPoint}",
            ];
        }

        return parent::healthCheck();
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
            return File::isDirectory($mountPoint) && is_writable($mountPoint);
        }
    }

    private function loginTarget(): bool
    {
        $portal = $this->getConfig('target_portal');
        $iqn = $this->getConfig('target_iqn');
        $username = $this->getConfig('username');
        $password = $this->getConfig('password');

        if (! $portal || ! $iqn) {
            return false;
        }

        try {
            // Discover target
            Process::timeout(10)->run("iscsiadm -m discovery -t sendtargets -p " . escapeshellarg($portal));

            // Set CHAP credentials if provided
            if ($username && $password) {
                Process::timeout(5)->run("iscsiadm -m node -T " . escapeshellarg($iqn) . " -p " . escapeshellarg($portal) . " --op update -n node.session.auth.authmethod -v CHAP");
                Process::timeout(5)->run("iscsiadm -m node -T " . escapeshellarg($iqn) . " -p " . escapeshellarg($portal) . " --op update -n node.session.auth.username -v " . escapeshellarg($username));
                Process::timeout(5)->run("iscsiadm -m node -T " . escapeshellarg($iqn) . " -p " . escapeshellarg($portal) . " --op update -n node.session.auth.password -v " . escapeshellarg($password));
            }

            // Login
            $result = Process::timeout(30)->run("iscsiadm -m node -T " . escapeshellarg($iqn) . " -p " . escapeshellarg($portal) . " --login");

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
