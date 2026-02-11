<?php

namespace App\Services\Storage\Drivers;

use App\Models\StorageBackend;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class LvmDriver extends LocalDriver
{
    public function getDriverType(): string
    {
        return StorageBackend::DRIVER_LVM;
    }

    public function connect(): bool
    {
        $mountPoint = $this->getConfig('mount_point');
        if (! $mountPoint) {
            return false;
        }

        // Verify mount is active
        if (! $this->isMounted()) {
            return false;
        }

        // Use mount_point as base_directory for LocalDriver operations
        $this->config['base_directory'] = $mountPoint;

        return parent::connect();
    }

    public function getCapacity(): array
    {
        $mountPoint = $this->getConfig('mount_point');
        if (! $mountPoint) {
            return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
        }

        $this->config['base_directory'] = $mountPoint;

        // Try LVM-specific capacity first
        $vg = $this->getConfig('volume_group');
        $lv = $this->getConfig('logical_volume');

        if ($vg && $lv) {
            try {
                $result = Process::timeout(10)->run("lvs --noheadings --nosuffix --units g -o lv_size {$vg}/{$lv}");
                if ($result->successful()) {
                    $lvSize = (float) trim($result->output());
                    if ($lvSize > 0) {
                        $capacity = parent::getCapacity();
                        $capacity['total_gb'] = round($lvSize, 2);

                        return $capacity;
                    }
                }
            } catch (\Throwable) {
                // Fall through to parent
            }
        }

        return parent::getCapacity();
    }

    public function healthCheck(): array
    {
        $mountPoint = $this->getConfig('mount_point');
        $this->config['base_directory'] = $mountPoint;

        // Check if LV is mounted
        if (! $this->isMounted()) {
            return [
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => 0,
                'message' => "Volume logico nao esta montado em {$mountPoint}",
            ];
        }

        return parent::healthCheck();
    }

    private function isMounted(): bool
    {
        $mountPoint = $this->getConfig('mount_point');
        if (! $mountPoint || ! File::isDirectory($mountPoint)) {
            return false;
        }

        try {
            $result = Process::timeout(5)->run("mountpoint -q " . escapeshellarg($mountPoint));

            return $result->successful();
        } catch (\Throwable) {
            // Fallback: check if it's a directory with content
            return File::isDirectory($mountPoint) && is_writable($mountPoint);
        }
    }
}
