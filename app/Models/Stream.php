<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Stream extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    const STATUS_STARTING = 'starting';
    const STATUS_ACTIVE = 'active';
    const STATUS_ERROR = 'error';
    const STATUS_STOPPED = 'stopped';
    const STATUS_RECONNECTING = 'reconnecting';

    protected $fillable = [
        'camera_id', 'tenant_id',
        'status', 'mediamtx_path',
        'hls_url', 'webrtc_url', 'rtsp_input_url',
        'pid',
        'started_at', 'stopped_at', 'last_health_check',
        'error_message', 'metadata',
    ];

    protected $casts = [
        'pid' => 'integer',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'last_health_check' => 'datetime',
        'metadata' => 'array',
    ];

    // ─── Activity Log ─────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'mediamtx_path', 'camera_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Stream foi {$eventName}");
    }

    // ─── Relationships ────────────────────────────────────

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeRunning($query)
    {
        return $query->whereIn('status', [self::STATUS_STARTING, self::STATUS_ACTIVE, self::STATUS_RECONNECTING]);
    }

    public function scopeForCamera($query, string $cameraId)
    {
        return $query->where('camera_id', $cameraId);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Status Helpers ─────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRunning(): bool
    {
        return in_array($this->status, [self::STATUS_STARTING, self::STATUS_ACTIVE, self::STATUS_RECONNECTING]);
    }

    public function isStopped(): bool
    {
        return $this->status === self::STATUS_STOPPED;
    }

    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    public function markActive(): self
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'started_at' => $this->started_at ?? now(),
            'error_message' => null,
            'last_health_check' => now(),
        ]);

        return $this;
    }

    public function markError(string $message): self
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'error_message' => $message,
        ]);

        return $this;
    }

    public function markStopped(): self
    {
        $this->update([
            'status' => self::STATUS_STOPPED,
            'stopped_at' => now(),
        ]);

        return $this;
    }

    public function markReconnecting(): self
    {
        $this->update([
            'status' => self::STATUS_RECONNECTING,
        ]);

        return $this;
    }

    public function updateHealthCheck(array $metrics = []): self
    {
        $data = ['last_health_check' => now()];

        if ($metrics) {
            $data['metadata'] = array_merge($this->metadata ?? [], $metrics);
        }

        $this->update($data);

        return $this;
    }
}
