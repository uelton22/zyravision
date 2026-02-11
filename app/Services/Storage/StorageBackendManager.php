<?php

namespace App\Services\Storage;

use App\Contracts\StorageDriverInterface;
use App\Models\Camera;
use App\Models\Recording;
use App\Models\StorageBackend;
use App\Models\Tenant;
use App\Services\Storage\Drivers\AzureBlobDriver;
use App\Services\Storage\Drivers\CdntvDriver;
use App\Services\Storage\Drivers\CifsDriver;
use App\Services\Storage\Drivers\CustomDriver;
use App\Services\Storage\Drivers\FtpDriver;
use App\Services\Storage\Drivers\GcsDriver;
use App\Services\Storage\Drivers\IscsiDriver;
use App\Services\Storage\Drivers\LocalDriver;
use App\Services\Storage\Drivers\LvmDriver;
use App\Services\Storage\Drivers\MinioDriver;
use App\Services\Storage\Drivers\NasDriver;
use App\Services\Storage\Drivers\NfsDriver;
use App\Services\Storage\Drivers\S3Driver;
use App\Services\Storage\Drivers\SftpDriver;
use App\Services\Storage\Drivers\WebDavDriver;
use Illuminate\Support\Facades\Log;

class StorageBackendManager
{
    /**
     * Cache of instantiated drivers by backend ID.
     */
    private array $driverCache = [];

    /**
     * Get the default storage backend for a tenant.
     */
    public function getDefaultBackend(Tenant $tenant): ?StorageBackend
    {
        return StorageBackend::forTenant($tenant->id)
            ->active()
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get the best available backend for a tenant.
     * Uses: default > highest priority healthy > first active.
     */
    public function resolveBackend(Tenant $tenant): ?StorageBackend
    {
        // Try default first
        $default = $this->getDefaultBackend($tenant);
        if ($default && $default->isOnline()) {
            return $default;
        }

        // Failover: find next healthy backend by priority
        return StorageBackend::forTenant($tenant->id)
            ->active()
            ->whereIn('health_status', [StorageBackend::HEALTH_HEALTHY, StorageBackend::HEALTH_DEGRADED, StorageBackend::HEALTH_UNKNOWN])
            ->byPriority()
            ->first();
    }

    /**
     * Instantiate a driver from a StorageBackend model.
     */
    public function getDriver(StorageBackend $backend): StorageDriverInterface
    {
        if (isset($this->driverCache[$backend->id])) {
            return $this->driverCache[$backend->id];
        }

        $config = $backend->config ?? [];
        $driver = $this->createDriver($backend->driver, $config);
        $this->driverCache[$backend->id] = $driver;

        return $driver;
    }

    /**
     * Create a driver instance from type and config.
     */
    public function createDriver(string $driverType, array $config): StorageDriverInterface
    {
        return match ($driverType) {
            StorageBackend::DRIVER_LOCAL => new LocalDriver($config),
            StorageBackend::DRIVER_LVM => new LvmDriver($config),
            StorageBackend::DRIVER_NFS => new NfsDriver($config),
            StorageBackend::DRIVER_CIFS => new CifsDriver($config),
            StorageBackend::DRIVER_SFTP => new SftpDriver($config),
            StorageBackend::DRIVER_FTP => new FtpDriver($config),
            StorageBackend::DRIVER_NAS => new NasDriver($config),
            StorageBackend::DRIVER_CDNTV => new CdntvDriver($config),
            StorageBackend::DRIVER_ISCSI => new IscsiDriver($config),
            StorageBackend::DRIVER_MINIO => new MinioDriver($config),
            StorageBackend::DRIVER_S3 => new S3Driver($config),
            StorageBackend::DRIVER_AZURE_BLOB => new AzureBlobDriver($config),
            StorageBackend::DRIVER_GCS => new GcsDriver($config),
            StorageBackend::DRIVER_WEBDAV => new WebDavDriver($config),
            StorageBackend::DRIVER_CUSTOM => new CustomDriver($config),
            default => throw new \InvalidArgumentException("Driver de armazenamento desconhecido: {$driverType}"),
        };
    }

    /**
     * Test connectivity of a storage backend.
     */
    public function testConnection(StorageBackend $backend): array
    {
        try {
            $driver = $this->getDriver($backend);
            $healthResult = $driver->healthCheck();

            // Update backend health
            $backend->update([
                'health_status' => $healthResult['status'],
                'last_health_check_at' => now(),
                'health_check_latency_ms' => $healthResult['latency_ms'],
                'health_check_message' => $healthResult['message'],
            ]);

            // Try to update capacity
            $capacity = $driver->getCapacity();
            if ($capacity['total_gb'] !== null) {
                $backend->update([
                    'total_capacity_gb' => $capacity['total_gb'],
                    'used_capacity_gb' => $capacity['used_gb'],
                ]);
            }

            return [
                'success' => in_array($healthResult['status'], [StorageBackend::HEALTH_HEALTHY, StorageBackend::HEALTH_DEGRADED]),
                'status' => $healthResult['status'],
                'latency_ms' => $healthResult['latency_ms'],
                'message' => $healthResult['message'],
                'capacity' => $capacity,
            ];
        } catch (\Throwable $e) {
            $backend->update([
                'health_status' => StorageBackend::HEALTH_OFFLINE,
                'last_health_check_at' => now(),
                'health_check_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => StorageBackend::HEALTH_OFFLINE,
                'latency_ms' => 0,
                'message' => $e->getMessage(),
                'capacity' => ['total_gb' => null, 'used_gb' => null, 'free_gb' => null],
            ];
        }
    }

    /**
     * Run health checks on all active backends for a tenant.
     */
    public function runHealthChecks(Tenant $tenant): array
    {
        $backends = StorageBackend::forTenant($tenant->id)->active()->get();
        $results = [];

        foreach ($backends as $backend) {
            $results[$backend->id] = $this->testConnection($backend);
        }

        return $results;
    }

    /**
     * Run health checks on ALL active backends (all tenants).
     */
    public function runGlobalHealthChecks(): int
    {
        $backends = StorageBackend::where('is_active', true)->get();
        $count = 0;

        foreach ($backends as $backend) {
            try {
                $this->testConnection($backend);
                $count++;
            } catch (\Throwable $e) {
                Log::warning("Health check falhou para backend {$backend->id}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Resolve the storage path for a recording.
     */
    public function resolveStoragePath(Camera $camera, ?Recording $recording = null): string
    {
        $tenantId = $camera->tenant_id;
        $cameraId = $camera->id;
        $date = now()->format('Y/m/d');

        $path = "tenant_{$tenantId}/camera_{$cameraId}/{$date}";

        if ($recording) {
            $path .= '/' . $recording->id;
        }

        return $path;
    }

    /**
     * Get list of available drivers with their labels, categories, and config field definitions.
     */
    public function getAvailableDrivers(): array
    {
        $labels = StorageBackend::getDriverLabels();
        $categories = StorageBackend::getDriverCategoryMap();
        $configFields = StorageBackend::getDriverConfigFields();

        $drivers = [];
        foreach (StorageBackend::DRIVERS as $driver) {
            $drivers[] = [
                'value' => $driver,
                'label' => $labels[$driver] ?? ucfirst($driver),
                'category' => $categories[$driver] ?? 'other',
                'config_fields' => $configFields[$driver] ?? [],
            ];
        }

        return $drivers;
    }

    /**
     * Clear the driver cache.
     */
    public function clearCache(): void
    {
        $this->driverCache = [];
    }
}
