<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Recording extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    const STATUS_RECORDING = 'recording';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'camera_id', 'tenant_id', 'stream_id', 'storage_backend_id',
        'status', 'storage_path',
        'file_size_bytes', 'duration_seconds',
        'started_at', 'ended_at',
        'codec', 'resolution', 'fps',
        'metadata',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'fps' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ─── Activity Log ─────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'camera_id', 'duration_seconds'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Recording foi {$eventName}");
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

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function storageBackend(): BelongsTo
    {
        return $this->belongsTo(StorageBackend::class);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeForCamera($query, string $cameraId)
    {
        return $query->where('camera_id', $cameraId);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRecording($query)
    {
        return $query->where('status', self::STATUS_RECORDING);
    }

    // ─── Helpers ──────────────────────────────────────────

    public function isRecording(): bool
    {
        return $this->status === self::STATUS_RECORDING;
    }

    public function fileSizeMb(): float
    {
        return round($this->file_size_bytes / (1024 * 1024), 2);
    }

    public function formattedDuration(): string
    {
        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function markCompleted(?int $fileSize = null, ?int $duration = null): self
    {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'ended_at' => now(),
        ];

        if ($fileSize !== null) {
            $data['file_size_bytes'] = $fileSize;
        }
        if ($duration !== null) {
            $data['duration_seconds'] = $duration;
        }

        $this->update($data);

        return $this;
    }

    public function markFailed(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'ended_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);

        return $this;
    }
}
