<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecordingSegment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    // ─── Status Constants ───────────────────────────
    const STATUS_WRITING = 'writing';     // MediaMTX esta escrevendo
    const STATUS_READY = 'ready';         // Segmento completo, disponivel localmente
    const STATUS_UPLOADING = 'uploading'; // Em upload ao storage backend
    const STATUS_UPLOADED = 'uploaded';   // Upload concluido (pode ter copia local ou nao)
    const STATUS_FAILED = 'failed';       // Falha no processamento

    protected $fillable = [
        'camera_id',
        'tenant_id',
        'storage_backend_id',
        'starts_at',
        'ends_at',
        'duration_seconds',
        'local_path',
        'remote_path',
        'filename',
        'file_size_bytes',
        'status',
        'codec',
        'resolution',
        'fps',
        'thumbnail_path',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'duration_seconds' => 'integer',
        'file_size_bytes' => 'integer',
        'fps' => 'integer',
    ];

    // ─── Relationships ──────────────────────────────

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function storageBackend(): BelongsTo
    {
        return $this->belongsTo(StorageBackend::class);
    }

    // ─── Scopes ─────────────────────────────────────

    public function scopeForCamera($query, string $cameraId)
    {
        return $query->where('camera_id', $cameraId);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeReady($query)
    {
        return $query->whereIn('status', [self::STATUS_READY, self::STATUS_UPLOADED]);
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', [
            self::STATUS_READY,
            self::STATUS_UPLOADED,
            self::STATUS_UPLOADING,
        ]);
    }

    public function scopeInTimeRange($query, $start, $end)
    {
        return $query->where('starts_at', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->where('ends_at', '>=', $start)
                    ->orWhereNull('ends_at'); // Still writing
            });
    }

    public function scopePendingUpload($query)
    {
        return $query->where('status', self::STATUS_READY)
            ->whereNull('remote_path');
    }

    public function scopeLocalOnly($query)
    {
        return $query->whereNotNull('local_path')
            ->whereNull('remote_path');
    }

    // ─── Helpers ────────────────────────────────────

    public function isAvailable(): bool
    {
        return in_array($this->status, [
            self::STATUS_READY,
            self::STATUS_UPLOADED,
            self::STATUS_UPLOADING,
        ]);
    }

    public function isLocal(): bool
    {
        return $this->local_path && file_exists($this->local_path);
    }

    public function isRemote(): bool
    {
        return (bool) $this->remote_path;
    }

    public function markReady(int $fileSize = 0, ?int $durationSeconds = null): void
    {
        $this->update([
            'status' => self::STATUS_READY,
            'file_size_bytes' => $fileSize ?: $this->file_size_bytes,
            'ends_at' => now(),
            'duration_seconds' => $durationSeconds ?? (
                $this->starts_at ? (int) $this->starts_at->diffInSeconds(now()) : 0
            ),
        ]);
    }

    public function markUploaded(string $remotePath, ?string $storageBackendId = null): void
    {
        $data = [
            'status' => self::STATUS_UPLOADED,
            'remote_path' => $remotePath,
        ];

        if ($storageBackendId) {
            $data['storage_backend_id'] = $storageBackendId;
        }

        $this->update($data);
    }

    public function markFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Get the best path to serve this segment (local preferred, then remote).
     */
    public function getServablePath(): ?string
    {
        if ($this->isLocal()) {
            return $this->local_path;
        }

        return $this->remote_path;
    }

    public function getHumanSize(): string
    {
        $bytes = $this->file_size_bytes;
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }
}
