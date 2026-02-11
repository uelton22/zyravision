<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageAnalyticsDaily extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'storage_analytics_daily';

    protected $fillable = [
        'tenant_id', 'storage_backend_id', 'date',
        'total_bytes', 'total_segments',
        'segments_added', 'segments_deleted',
        'bytes_added', 'bytes_deleted',
        'growth_rate_percent',
    ];

    protected $casts = [
        'date' => 'date',
        'total_bytes' => 'integer',
        'total_segments' => 'integer',
        'segments_added' => 'integer',
        'segments_deleted' => 'integer',
        'bytes_added' => 'integer',
        'bytes_deleted' => 'integer',
        'growth_rate_percent' => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function storageBackend(): BelongsTo
    {
        return $this->belongsTo(StorageBackend::class);
    }
}
