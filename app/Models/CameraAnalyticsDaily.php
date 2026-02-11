<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraAnalyticsDaily extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'camera_analytics_daily';

    protected $fillable = [
        'camera_id', 'tenant_id', 'date',
        'uptime_seconds', 'downtime_seconds', 'availability_percent',
        'segments_count', 'recording_seconds', 'recording_bytes', 'recording_gaps',
        'stream_starts', 'stream_errors', 'stream_reconnections',
        'offline_events', 'error_events',
        'longest_offline_start', 'longest_offline_seconds',
    ];

    protected $casts = [
        'date' => 'date',
        'uptime_seconds' => 'integer',
        'downtime_seconds' => 'integer',
        'availability_percent' => 'float',
        'segments_count' => 'integer',
        'recording_seconds' => 'integer',
        'recording_bytes' => 'integer',
        'recording_gaps' => 'integer',
        'stream_starts' => 'integer',
        'stream_errors' => 'integer',
        'stream_reconnections' => 'integer',
        'offline_events' => 'integer',
        'error_events' => 'integer',
        'longest_offline_start' => 'datetime',
        'longest_offline_seconds' => 'integer',
    ];

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
