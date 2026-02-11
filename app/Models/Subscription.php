<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'plan_id', 'status',
        'started_at', 'trial_ends_at',
        'current_period_start', 'current_period_end',
        'cancelled_at', 'cancellation_reason', 'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trial']);
    }

    // ─── Status Helpers ───────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->current_period_end && $this->current_period_end->isPast());
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function daysRemaining(): int
    {
        if ($this->isOnTrial()) {
            return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
        }

        if ($this->current_period_end) {
            return max(0, (int) now()->diffInDays($this->current_period_end, false));
        }

        return 0;
    }
}
