<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotaUsage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'resource', 'current_usage', 'limit_value', 'last_calculated_at',
    ];

    protected $casts = [
        'current_usage' => 'integer',
        'limit_value' => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Helpers ──────────────────────────────────────────

    public function getPercentageUsed(): float
    {
        if ($this->limit_value <= 0) return 0;
        return round(($this->current_usage / $this->limit_value) * 100, 1);
    }

    public function isExceeded(): bool
    {
        return $this->current_usage >= $this->limit_value;
    }

    public function isNearLimit(int $thresholdPercent = 80): bool
    {
        return $this->getPercentageUsed() >= $thresholdPercent;
    }

    public function remaining(): int
    {
        return max(0, $this->limit_value - $this->current_usage);
    }
}
