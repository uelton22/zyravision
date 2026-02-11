<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StorageBackend extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    // ─── Driver Constants ──────────────────────────────────
    const DRIVER_LOCAL = 'local';
    const DRIVER_LVM = 'lvm';
    const DRIVER_NAS = 'nas';
    const DRIVER_NFS = 'nfs';
    const DRIVER_CIFS = 'cifs';
    const DRIVER_SFTP = 'sftp';
    const DRIVER_FTP = 'ftp';
    const DRIVER_CDNTV = 'cdntv';
    const DRIVER_MINIO = 'minio';
    const DRIVER_S3 = 's3';
    const DRIVER_AZURE_BLOB = 'azure_blob';
    const DRIVER_GCS = 'gcs';
    const DRIVER_WEBDAV = 'webdav';
    const DRIVER_ISCSI = 'iscsi';
    const DRIVER_CUSTOM = 'custom';

    const DRIVERS = [
        self::DRIVER_LOCAL,
        self::DRIVER_LVM,
        self::DRIVER_NAS,
        self::DRIVER_NFS,
        self::DRIVER_CIFS,
        self::DRIVER_SFTP,
        self::DRIVER_FTP,
        self::DRIVER_CDNTV,
        self::DRIVER_MINIO,
        self::DRIVER_S3,
        self::DRIVER_AZURE_BLOB,
        self::DRIVER_GCS,
        self::DRIVER_WEBDAV,
        self::DRIVER_ISCSI,
        self::DRIVER_CUSTOM,
    ];

    // ─── Health Status Constants ───────────────────────────
    const HEALTH_HEALTHY = 'healthy';
    const HEALTH_DEGRADED = 'degraded';
    const HEALTH_OFFLINE = 'offline';
    const HEALTH_UNKNOWN = 'unknown';

    // ─── Driver Categories ────────────────────────────────
    const CATEGORY_LOCAL = 'local';
    const CATEGORY_NETWORK = 'network';
    const CATEGORY_CLOUD = 'cloud';
    const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'name',
        'driver',
        'is_active',
        'is_default',
        'priority',
        'config',
        'health_status',
        'last_health_check_at',
        'health_check_latency_ms',
        'health_check_message',
        'total_capacity_gb',
        'used_capacity_gb',
        'total_files',
        'total_bytes_written',
    ];

    protected $casts = [
        'config' => 'encrypted:array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'total_capacity_gb' => 'decimal:2',
        'used_capacity_gb' => 'decimal:2',
        'total_files' => 'integer',
        'total_bytes_written' => 'integer',
        'health_check_latency_ms' => 'integer',
        'last_health_check_at' => 'datetime',
    ];

    protected $hidden = [
        'config', // Never expose encrypted config in API responses
    ];

    // ─── Activity Log ─────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'driver', 'is_active', 'is_default', 'health_status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "StorageBackend foi {$eventName}");
    }

    // ─── Relationships ────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeForTenant($query, ?string $tenantId)
    {
        // Include both tenant-specific AND global (null) backends
        return $query->where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhereNull('tenant_id');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('health_status', self::HEALTH_HEALTHY);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority')->orderBy('name');
    }

    // ─── Helpers ──────────────────────────────────────────

    public function isHealthy(): bool
    {
        return $this->health_status === self::HEALTH_HEALTHY;
    }

    public function isOnline(): bool
    {
        return in_array($this->health_status, [self::HEALTH_HEALTHY, self::HEALTH_DEGRADED]);
    }

    public function freeCapacityGb(): ?float
    {
        if ($this->total_capacity_gb === null || $this->used_capacity_gb === null) {
            return null;
        }

        return max(0, (float) $this->total_capacity_gb - (float) $this->used_capacity_gb);
    }

    public function usagePercent(): ?float
    {
        if (! $this->total_capacity_gb || (float) $this->total_capacity_gb === 0.0) {
            return null;
        }

        return round(((float) $this->used_capacity_gb / (float) $this->total_capacity_gb) * 100, 1);
    }

    public function getDriverLabel(): string
    {
        return self::getDriverLabels()[$this->driver] ?? ucfirst($this->driver);
    }

    public function getDriverCategory(): string
    {
        return self::getDriverCategoryMap()[$this->driver] ?? self::CATEGORY_OTHER;
    }

    /**
     * Get a config value safely.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Get safe config for frontend display (no sensitive data).
     */
    public function getSafeConfig(): array
    {
        $config = $this->config ?? [];
        $sensitiveKeys = ['password', 'secret_key', 'secret_access_key', 'account_key', 'private_key', 'key_passphrase', 'api_key', 'session_token', 'connection_string'];

        $safe = [];
        foreach ($config as $key => $value) {
            if (in_array($key, $sensitiveKeys)) {
                $safe[$key] = $value ? '••••••••' : null;
            } else {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }

    // ─── Static Helpers ───────────────────────────────────

    /**
     * Ensure only one default backend per tenant (or globally if tenant_id is null).
     */
    public function markAsDefault(): void
    {
        // Unset all other defaults for this tenant (or global scope)
        $query = static::where('id', '!=', $this->id);

        if ($this->tenant_id) {
            $query->where('tenant_id', $this->tenant_id);
        } else {
            $query->whereNull('tenant_id');
        }

        $query->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get all driver labels for frontend display.
     */
    public static function getDriverLabels(): array
    {
        return [
            self::DRIVER_LOCAL => 'Local (HDD/SSD)',
            self::DRIVER_LVM => 'LVM (Logical Volume)',
            self::DRIVER_NAS => 'NAS Generico',
            self::DRIVER_NFS => 'NFS',
            self::DRIVER_CIFS => 'CIFS/SMB',
            self::DRIVER_SFTP => 'SFTP',
            self::DRIVER_FTP => 'FTP',
            self::DRIVER_CDNTV => 'CDN TV',
            self::DRIVER_MINIO => 'MinIO',
            self::DRIVER_S3 => 'AWS S3',
            self::DRIVER_AZURE_BLOB => 'Azure Blob Storage',
            self::DRIVER_GCS => 'Google Cloud Storage',
            self::DRIVER_WEBDAV => 'WebDAV',
            self::DRIVER_ISCSI => 'iSCSI',
            self::DRIVER_CUSTOM => 'Personalizado',
        ];
    }

    /**
     * Map drivers to categories.
     */
    public static function getDriverCategoryMap(): array
    {
        return [
            self::DRIVER_LOCAL => self::CATEGORY_LOCAL,
            self::DRIVER_LVM => self::CATEGORY_LOCAL,
            self::DRIVER_NAS => self::CATEGORY_NETWORK,
            self::DRIVER_NFS => self::CATEGORY_NETWORK,
            self::DRIVER_CIFS => self::CATEGORY_NETWORK,
            self::DRIVER_SFTP => self::CATEGORY_NETWORK,
            self::DRIVER_FTP => self::CATEGORY_NETWORK,
            self::DRIVER_CDNTV => self::CATEGORY_NETWORK,
            self::DRIVER_ISCSI => self::CATEGORY_NETWORK,
            self::DRIVER_WEBDAV => self::CATEGORY_NETWORK,
            self::DRIVER_MINIO => self::CATEGORY_CLOUD,
            self::DRIVER_S3 => self::CATEGORY_CLOUD,
            self::DRIVER_AZURE_BLOB => self::CATEGORY_CLOUD,
            self::DRIVER_GCS => self::CATEGORY_CLOUD,
            self::DRIVER_CUSTOM => self::CATEGORY_OTHER,
        ];
    }

    /**
     * Get config field definitions per driver (for frontend forms).
     */
    public static function getDriverConfigFields(): array
    {
        return [
            self::DRIVER_LOCAL => [
                ['key' => 'base_directory', 'label' => 'Diretorio Base', 'type' => 'text', 'required' => true, 'placeholder' => '/recordings'],
                ['key' => 'max_capacity_gb', 'label' => 'Capacidade Maxima (GB)', 'type' => 'number', 'required' => false],
            ],
            self::DRIVER_LVM => [
                ['key' => 'volume_group', 'label' => 'Volume Group', 'type' => 'text', 'required' => true, 'placeholder' => 'vg_recordings'],
                ['key' => 'logical_volume', 'label' => 'Logical Volume', 'type' => 'text', 'required' => true, 'placeholder' => 'lv_data'],
                ['key' => 'mount_point', 'label' => 'Ponto de Montagem', 'type' => 'text', 'required' => true, 'placeholder' => '/mnt/lvm_recordings'],
                ['key' => 'max_capacity_gb', 'label' => 'Capacidade Maxima (GB)', 'type' => 'number', 'required' => false],
            ],
            self::DRIVER_NFS => [
                ['key' => 'server', 'label' => 'Servidor (IP/Hostname)', 'type' => 'text', 'required' => true, 'placeholder' => '192.168.1.100'],
                ['key' => 'port', 'label' => 'Porta', 'type' => 'number', 'required' => false, 'placeholder' => '2049'],
                ['key' => 'export_path', 'label' => 'Export Path', 'type' => 'text', 'required' => true, 'placeholder' => '/volume1/recordings'],
                ['key' => 'mount_point', 'label' => 'Ponto de Montagem Local', 'type' => 'text', 'required' => true, 'placeholder' => '/mnt/nfs_recordings'],
                ['key' => 'nfs_version', 'label' => 'Versao NFS', 'type' => 'select', 'required' => false, 'options' => ['3', '4', '4.1', '4.2']],
                ['key' => 'mount_options', 'label' => 'Opcoes de Montagem', 'type' => 'text', 'required' => false, 'placeholder' => 'rw,sync,hard,intr'],
                ['key' => 'username', 'label' => 'Usuario (Kerberos)', 'type' => 'text', 'required' => false],
                ['key' => 'password', 'label' => 'Senha (Kerberos)', 'type' => 'password', 'required' => false],
            ],
            self::DRIVER_CIFS => [
                ['key' => 'server', 'label' => 'Servidor (IP/Hostname)', 'type' => 'text', 'required' => true, 'placeholder' => '192.168.1.100'],
                ['key' => 'port', 'label' => 'Porta', 'type' => 'number', 'required' => false, 'placeholder' => '445'],
                ['key' => 'share_name', 'label' => 'Nome do Compartilhamento', 'type' => 'text', 'required' => true, 'placeholder' => 'recordings'],
                ['key' => 'mount_point', 'label' => 'Ponto de Montagem Local', 'type' => 'text', 'required' => true, 'placeholder' => '/mnt/cifs_recordings'],
                ['key' => 'domain', 'label' => 'Dominio', 'type' => 'text', 'required' => false, 'placeholder' => 'WORKGROUP'],
                ['key' => 'username', 'label' => 'Usuario', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Senha', 'type' => 'password', 'required' => true],
                ['key' => 'smb_version', 'label' => 'Versao SMB', 'type' => 'select', 'required' => false, 'options' => ['2.0', '2.1', '3.0', '3.1.1']],
                ['key' => 'mount_options', 'label' => 'Opcoes de Montagem', 'type' => 'text', 'required' => false],
            ],
            self::DRIVER_SFTP => [
                ['key' => 'server', 'label' => 'Servidor (IP/Hostname)', 'type' => 'text', 'required' => true, 'placeholder' => '192.168.1.100'],
                ['key' => 'port', 'label' => 'Porta', 'type' => 'number', 'required' => false, 'placeholder' => '22'],
                ['key' => 'base_directory', 'label' => 'Diretorio Base', 'type' => 'text', 'required' => true, 'placeholder' => '/home/recordings'],
                ['key' => 'username', 'label' => 'Usuario', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Senha', 'type' => 'password', 'required' => false],
                ['key' => 'private_key', 'label' => 'Chave Privada SSH', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Cole a chave SSH aqui...'],
                ['key' => 'key_passphrase', 'label' => 'Passphrase da Chave', 'type' => 'password', 'required' => false],
                ['key' => 'host_fingerprint', 'label' => 'Fingerprint do Host', 'type' => 'text', 'required' => false],
            ],
            self::DRIVER_FTP => [
                ['key' => 'server', 'label' => 'Servidor (IP/Hostname)', 'type' => 'text', 'required' => true, 'placeholder' => '192.168.1.100'],
                ['key' => 'port', 'label' => 'Porta', 'type' => 'number', 'required' => false, 'placeholder' => '21'],
                ['key' => 'base_directory', 'label' => 'Diretorio Base', 'type' => 'text', 'required' => true, 'placeholder' => '/recordings'],
                ['key' => 'username', 'label' => 'Usuario', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Senha', 'type' => 'password', 'required' => true],
                ['key' => 'use_ssl', 'label' => 'Usar FTPS (SSL/TLS)', 'type' => 'boolean', 'required' => false],
                ['key' => 'passive_mode', 'label' => 'Modo Passivo', 'type' => 'boolean', 'required' => false],
            ],
            self::DRIVER_NAS => [
                ['key' => 'protocol', 'label' => 'Protocolo', 'type' => 'select', 'required' => true, 'options' => ['nfs', 'cifs', 'iscsi']],
                ['key' => 'server', 'label' => 'Servidor (IP/Hostname)', 'type' => 'text', 'required' => true, 'placeholder' => '192.168.1.100'],
                ['key' => 'port', 'label' => 'Porta', 'type' => 'number', 'required' => false],
                ['key' => 'share_path', 'label' => 'Caminho do Compartilhamento', 'type' => 'text', 'required' => true, 'placeholder' => '/volume1/recordings'],
                ['key' => 'mount_point', 'label' => 'Ponto de Montagem Local', 'type' => 'text', 'required' => false, 'placeholder' => '/mnt/nas'],
                ['key' => 'username', 'label' => 'Usuario', 'type' => 'text', 'required' => false],
                ['key' => 'password', 'label' => 'Senha', 'type' => 'password', 'required' => false],
            ],
            self::DRIVER_CDNTV => [
                ['key' => 'server', 'label' => 'Servidor (IP/Hostname)', 'type' => 'text', 'required' => true],
                ['key' => 'port', 'label' => 'Porta', 'type' => 'number', 'required' => false],
                ['key' => 'api_endpoint', 'label' => 'Endpoint da API', 'type' => 'text', 'required' => true, 'placeholder' => 'https://api.cdntv.com/v1'],
                ['key' => 'api_key', 'label' => 'Chave API', 'type' => 'password', 'required' => true],
                ['key' => 'username', 'label' => 'Usuario', 'type' => 'text', 'required' => false],
                ['key' => 'password', 'label' => 'Senha', 'type' => 'password', 'required' => false],
                ['key' => 'base_directory', 'label' => 'Diretorio Base', 'type' => 'text', 'required' => false],
                ['key' => 'retention_policy', 'label' => 'Politica de Retencao', 'type' => 'text', 'required' => false],
            ],
            self::DRIVER_ISCSI => [
                ['key' => 'target_portal', 'label' => 'Portal iSCSI (IP:Porta)', 'type' => 'text', 'required' => true, 'placeholder' => '192.168.1.100:3260'],
                ['key' => 'target_iqn', 'label' => 'Target IQN', 'type' => 'text', 'required' => true, 'placeholder' => 'iqn.2024-01.com.example:storage'],
                ['key' => 'mount_point', 'label' => 'Ponto de Montagem', 'type' => 'text', 'required' => true, 'placeholder' => '/mnt/iscsi_recordings'],
                ['key' => 'username', 'label' => 'Usuario CHAP', 'type' => 'text', 'required' => false],
                ['key' => 'password', 'label' => 'Senha CHAP', 'type' => 'password', 'required' => false],
                ['key' => 'filesystem', 'label' => 'Sistema de Arquivos', 'type' => 'select', 'required' => false, 'options' => ['ext4', 'xfs', 'btrfs']],
            ],
            self::DRIVER_MINIO => [
                ['key' => 'endpoint', 'label' => 'Endpoint', 'type' => 'text', 'required' => true, 'placeholder' => 'http://minio.local:9000'],
                ['key' => 'port', 'label' => 'Porta', 'type' => 'number', 'required' => false, 'placeholder' => '9000'],
                ['key' => 'access_key', 'label' => 'Access Key', 'type' => 'text', 'required' => true],
                ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true],
                ['key' => 'bucket', 'label' => 'Bucket', 'type' => 'text', 'required' => true, 'placeholder' => 'zyravision-recordings'],
                ['key' => 'region', 'label' => 'Regiao', 'type' => 'text', 'required' => false, 'placeholder' => 'us-east-1'],
                ['key' => 'use_ssl', 'label' => 'Usar SSL', 'type' => 'boolean', 'required' => false],
                ['key' => 'path_style', 'label' => 'Path Style', 'type' => 'boolean', 'required' => false],
            ],
            self::DRIVER_S3 => [
                ['key' => 'region', 'label' => 'Regiao AWS', 'type' => 'text', 'required' => true, 'placeholder' => 'sa-east-1'],
                ['key' => 'bucket', 'label' => 'Bucket', 'type' => 'text', 'required' => true, 'placeholder' => 'zyravision-recordings'],
                ['key' => 'prefix', 'label' => 'Prefixo', 'type' => 'text', 'required' => false, 'placeholder' => 'recordings/'],
                ['key' => 'access_key_id', 'label' => 'Access Key ID', 'type' => 'text', 'required' => true],
                ['key' => 'secret_access_key', 'label' => 'Secret Access Key', 'type' => 'password', 'required' => true],
                ['key' => 'session_token', 'label' => 'Session Token', 'type' => 'password', 'required' => false],
                ['key' => 'storage_class', 'label' => 'Storage Class', 'type' => 'select', 'required' => false, 'options' => ['STANDARD', 'INTELLIGENT_TIERING', 'STANDARD_IA', 'ONEZONE_IA', 'GLACIER', 'GLACIER_IR', 'DEEP_ARCHIVE']],
                ['key' => 'encryption', 'label' => 'Criptografia', 'type' => 'select', 'required' => false, 'options' => ['none', 'AES256', 'aws:kms']],
                ['key' => 'kms_key_id', 'label' => 'KMS Key ID', 'type' => 'text', 'required' => false],
            ],
            self::DRIVER_AZURE_BLOB => [
                ['key' => 'account_name', 'label' => 'Account Name', 'type' => 'text', 'required' => true],
                ['key' => 'account_key', 'label' => 'Account Key', 'type' => 'password', 'required' => false],
                ['key' => 'connection_string', 'label' => 'Connection String', 'type' => 'password', 'required' => false],
                ['key' => 'container', 'label' => 'Container', 'type' => 'text', 'required' => true, 'placeholder' => 'zyravision-recordings'],
                ['key' => 'prefix', 'label' => 'Prefixo', 'type' => 'text', 'required' => false],
                ['key' => 'access_tier', 'label' => 'Access Tier', 'type' => 'select', 'required' => false, 'options' => ['Hot', 'Cool', 'Archive']],
                ['key' => 'encryption_scope', 'label' => 'Encryption Scope', 'type' => 'text', 'required' => false],
            ],
            self::DRIVER_GCS => [
                ['key' => 'project_id', 'label' => 'Project ID', 'type' => 'text', 'required' => true],
                ['key' => 'bucket', 'label' => 'Bucket', 'type' => 'text', 'required' => true, 'placeholder' => 'zyravision-recordings'],
                ['key' => 'prefix', 'label' => 'Prefixo', 'type' => 'text', 'required' => false],
                ['key' => 'key_file_json', 'label' => 'Service Account Key (JSON)', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Cole o JSON da chave aqui...'],
                ['key' => 'storage_class', 'label' => 'Storage Class', 'type' => 'select', 'required' => false, 'options' => ['STANDARD', 'NEARLINE', 'COLDLINE', 'ARCHIVE']],
            ],
            self::DRIVER_WEBDAV => [
                ['key' => 'base_url', 'label' => 'URL Base', 'type' => 'text', 'required' => true, 'placeholder' => 'https://webdav.example.com/remote.php/dav/files/user/'],
                ['key' => 'username', 'label' => 'Usuario', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Senha', 'type' => 'password', 'required' => true],
                ['key' => 'base_directory', 'label' => 'Diretorio Base', 'type' => 'text', 'required' => false, 'placeholder' => '/recordings'],
                ['key' => 'verify_ssl', 'label' => 'Verificar SSL', 'type' => 'boolean', 'required' => false],
            ],
            self::DRIVER_CUSTOM => [
                ['key' => 'driver_class', 'label' => 'Classe do Driver (FQCN)', 'type' => 'text', 'required' => true, 'placeholder' => 'App\\Services\\Storage\\Drivers\\MyCustomDriver'],
                ['key' => 'custom_config', 'label' => 'Configuracao JSON', 'type' => 'textarea', 'required' => false, 'placeholder' => '{"key": "value"}'],
            ],
        ];
    }
}
