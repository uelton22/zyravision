<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Camera extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    const CHANNEL_TYPES = ['analog', 'ip', 'direct'];

    const STREAM_TYPES = ['main', 'sub', 'mobile'];

    protected $fillable = [
        'tenant_id', 'device_id', 'name', 'slug',
        'channel_number', 'channel_type',
        'manufacturer', 'model',
        'address_type', 'address', 'port',
        'secondary_address', 'transport',
        'username', 'password',
        'rtsp_path', 'rtmp_path', 'hls_path', 'stream_type', 'codec', 'resolution', 'fps',
        'ddns_provider', 'ddns_hostname', 'ipv6_preferred',
        'last_resolved_ip', 'last_resolved_at',
        'has_ptz', 'has_audio',
        'is_active', 'is_online', 'is_recording', 'continuous_recording', 'last_seen_at',
        'recording_quality', 'recording_resolution', 'recording_codec', 'recording_fps', 'recording_bitrate',
        'location', 'description', 'snapshot_url', 'metadata',
    ];

    protected $casts = [
        'channel_number' => 'integer',
        'port' => 'integer',
        'fps' => 'integer',
        'has_ptz' => 'boolean',
        'has_audio' => 'boolean',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'is_recording' => 'boolean',
        'continuous_recording' => 'boolean',
        'recording_fps' => 'integer',
        'ipv6_preferred' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_resolved_at' => 'datetime',
        'metadata' => 'array',
        'password' => 'encrypted',
    ];

    protected $hidden = ['password'];

    // ─── Activity Log ─────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'address', 'is_active', 'device_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Camera foi {$eventName}");
    }

    // ─── Relationships ────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function activeStream(): HasOne
    {
        return $this->hasOne(Stream::class)
            ->whereIn('status', ['starting', 'active', 'reconnecting'])
            ->latest('started_at');
    }

    /**
     * Latest stream including error state (for dashboard display).
     */
    public function latestStream(): HasOne
    {
        return $this->hasOne(Stream::class)
            ->whereIn('status', ['starting', 'active', 'reconnecting', 'error'])
            ->latest('started_at');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
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

    public function scopeDirect($query)
    {
        return $query->whereNull('device_id');
    }

    public function scopeFromDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    // ─── Helpers ──────────────────────────────────────────

    public function isDirect(): bool
    {
        return $this->device_id === null;
    }

    public function getEffectiveManufacturer(): ?string
    {
        return $this->manufacturer ?? $this->device?->manufacturer;
    }

    public function getEffectiveAddress(): string
    {
        return $this->address ?? $this->device?->address ?? '';
    }

    public function getEffectivePort(): int
    {
        return $this->port ?? $this->device?->port ?? 554;
    }

    // ─── Recording Quality ──────────────────────────────

    const QUALITY_PRESETS = [
        'original' => ['resolution' => null, 'codec' => 'copy', 'fps' => null, 'bitrate' => null],
        'high'     => ['resolution' => '1920x1080', 'codec' => 'h264', 'fps' => 30, 'bitrate' => '4M'],
        'medium'   => ['resolution' => '1280x720', 'codec' => 'h264', 'fps' => 15, 'bitrate' => '2M'],
        'low'      => ['resolution' => '640x480', 'codec' => 'h264', 'fps' => 10, 'bitrate' => '1M'],
    ];

    /**
     * Get the effective recording quality, considering per-camera override and tenant defaults.
     */
    public function getEffectiveRecordingQuality(): string
    {
        if ($this->recording_quality) {
            return $this->recording_quality;
        }

        $settings = RecordingSetting::forTenant($this->tenant_id);

        return $settings->recording_quality ?? 'original';
    }

    /**
     * Whether this camera uses FFmpeg for recording.
     * Always true: MPEG-TS segments via FFmpeg are required for HLS playback,
     * even with 'original' quality (copy codec, no re-encoding).
     */
    public function needsTranscoding(): bool
    {
        return true;
    }

    /**
     * Get the resolved recording parameters (resolution, codec, fps, bitrate).
     */
    public function getRecordingParams(): array
    {
        $quality = $this->getEffectiveRecordingQuality();

        if ($quality === 'original') {
            return [
                'resolution' => null,
                'codec' => 'copy',
                'fps' => null,
                'bitrate' => null,
            ];
        }

        if ($quality === 'custom') {
            return [
                'resolution' => $this->recording_resolution ?? '1280x720',
                'codec' => $this->recording_codec ?? 'h264',
                'fps' => $this->recording_fps ?? 15,
                'bitrate' => $this->recording_bitrate ?? '2M',
            ];
        }

        // Named preset (high, medium, low)
        $preset = self::QUALITY_PRESETS[$quality] ?? self::QUALITY_PRESETS['medium'];

        return [
            'resolution' => $preset['resolution'],
            'codec' => $preset['codec'],
            'fps' => $preset['fps'],
            'bitrate' => $preset['bitrate'],
        ];
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
