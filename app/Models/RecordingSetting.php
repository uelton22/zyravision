<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordingSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'segment_duration_seconds',
        'continuous_recording_default',
        'retention_days',
        'cleanup_local_after_upload',
        'recording_quality',
        'default_storage_backend_id',
    ];

    protected $casts = [
        'segment_duration_seconds' => 'integer',
        'continuous_recording_default' => 'boolean',
        'retention_days' => 'integer',
        'cleanup_local_after_upload' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function defaultStorageBackend(): BelongsTo
    {
        return $this->belongsTo(StorageBackend::class, 'default_storage_backend_id');
    }

    // ─── Static Helpers ─────────────────────────────

    /**
     * Get settings for a tenant (or global defaults).
     */
    public static function forTenant(?string $tenantId): self
    {
        if ($tenantId) {
            $settings = static::where('tenant_id', $tenantId)->first();
            if ($settings) {
                return $settings;
            }
        }

        // Global settings (null tenant)
        $global = static::whereNull('tenant_id')->first();
        if ($global) {
            return $global;
        }

        // Return defaults
        return new static([
            'segment_duration_seconds' => 60,
            'continuous_recording_default' => true,
            'retention_days' => 30,
            'cleanup_local_after_upload' => true,
            'recording_quality' => 'original',
        ]);
    }

    /**
     * Get the segment duration for MediaMTX config format.
     */
    public function getMediaMtxSegmentDuration(): string
    {
        $seconds = $this->segment_duration_seconds;

        if ($seconds >= 60 && $seconds % 60 === 0) {
            return ($seconds / 60) . 'm';
        }

        return $seconds . 's';
    }
}
