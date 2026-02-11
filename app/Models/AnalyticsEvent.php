<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'camera_id',
        'event_type', 'severity', 'description', 'metadata',
        'occurred_at', 'resolved_at', 'is_resolved',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_resolved' => 'boolean',
    ];

    // Event types
    const TYPE_CAMERA_OFFLINE = 'camera_offline';
    const TYPE_CAMERA_ONLINE = 'camera_online';
    const TYPE_RECORDING_GAP = 'recording_gap';
    const TYPE_STREAM_ERROR = 'stream_error';
    const TYPE_STORAGE_WARNING = 'storage_warning';
    const TYPE_STORAGE_CRITICAL = 'storage_critical';
    const TYPE_MOTION_DETECTED = 'motion_detected';
    const TYPE_SYSTEM_ERROR = 'system_error';

    // Severities
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    // ─── Scopes ──────────────────────────

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }
}
