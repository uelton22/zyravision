<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Device extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    const TYPES = ['dvr', 'nvr', 'encoder', 'camera', 'hybrid'];

    const ADDRESS_TYPES = ['ipv4', 'ipv6', 'ddns', 'hostname'];

    const TRANSPORTS = ['tcp', 'udp', 'auto'];

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'type',
        'manufacturer', 'model', 'firmware_version',
        'address_type', 'address', 'port', 'http_port', 'rtmp_port',
        'secondary_address', 'transport',
        'username', 'password',
        'total_channels', 'active_channels',
        'onvif_supported', 'onvif_profile',
        'is_active', 'is_online', 'last_seen_at', 'uptime_seconds',
        'ddns_provider', 'ddns_hostname', 'ipv6_preferred',
        'last_resolved_ip', 'last_resolved_at',
        'location', 'serial_number', 'metadata',
        'storage_used_gb', 'storage_updated_at',
        'pack_model', 'hls_enabled', 'hls_fmp4_enabled', 'low_latency_hls_enabled',
    ];

    protected $casts = [
        'port' => 'integer',
        'http_port' => 'integer',
        'rtmp_port' => 'integer',
        'total_channels' => 'integer',
        'active_channels' => 'integer',
        'onvif_supported' => 'boolean',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'last_seen_at' => 'datetime',
        'uptime_seconds' => 'integer',
        'ipv6_preferred' => 'boolean',
        'last_resolved_at' => 'datetime',
        'metadata' => 'array',
        'storage_used_gb' => 'decimal:2',
        'storage_updated_at' => 'datetime',
        'hls_enabled' => 'boolean',
        'hls_fmp4_enabled' => 'boolean',
        'low_latency_hls_enabled' => 'boolean',
        'password' => 'encrypted',
    ];

    protected $hidden = ['password'];

    // ─── Activity Log ─────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'manufacturer', 'address', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Dispositivo foi {$eventName}");
    }

    // ─── Relationships ────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }

    public function activeChannels(): HasMany
    {
        return $this->hasMany(Camera::class)->where('is_active', true);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ─── Helpers ──────────────────────────────────────────

    public function isDvr(): bool
    {
        return in_array($this->type, ['dvr', 'nvr', 'hybrid']);
    }

    public function isStandaloneCamera(): bool
    {
        return $this->type === 'camera';
    }

    public function getEffectiveAddress(): string
    {
        return $this->last_resolved_ip ?? $this->address;
    }

    public function getRtspUrl(int $channel, string $streamType = 'main'): string
    {
        $resolver = app(\App\Services\Manufacturers\ManufacturerResolver::class);
        $profile = $resolver->getProfile($this->manufacturer);

        return $profile->buildRtspUrl($this, $channel, $streamType);
    }

    public function getRtmpUrl(int $channel, string $streamType = 'main'): string
    {
        $resolver = app(\App\Services\Manufacturers\ManufacturerResolver::class);
        $profile = $resolver->getProfile($this->manufacturer);

        return $profile->buildRtmpUrl($this, $channel, $streamType);
    }

    public static function generateSlug(string $name, string $tenantId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::withTrashed()->where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
