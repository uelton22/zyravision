<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'domain', 'logo_url',
        'is_active', 'settings',
        'max_cameras', 'max_users', 'storage_retention_days',
        'plan', 'trial_ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'max_cameras' => 'integer',
        'max_users' => 'integer',
        'storage_retention_days' => 'integer',
        'trial_ends_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->latest('started_at');
    }

    public function quotaUsages(): HasMany
    {
        return $this->hasMany(QuotaUsage::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Plan / Subscription Helpers ──────────────────────

    public function currentPlan(): ?Plan
    {
        // First try: get plan from active subscription
        $plan = $this->activeSubscription?->plan;

        // Fallback: lookup plan by the tenant's plan slug field
        if (!$plan && $this->plan) {
            $plan = Plan::where('slug', $this->plan)->first();
        }

        return $plan;
    }

    public function isOnTrial(): bool
    {
        $sub = $this->activeSubscription;
        return $sub && $sub->status === 'trial' && $sub->trial_ends_at?->isFuture();
    }

    public function subscriptionStatus(): string
    {
        $sub = $this->activeSubscription;
        if (!$sub) return 'none';
        return $sub->status;
    }

    public function trialDaysRemaining(): int
    {
        $sub = $this->activeSubscription;
        if (!$sub || !$sub->isOnTrial()) return 0;
        return $sub->daysRemaining();
    }

    // ─── Quota Helpers ────────────────────────────────────

    public function getQuota(string $resource): ?QuotaUsage
    {
        return $this->quotaUsages()->where('resource', $resource)->first();
    }

    public function hasReachedUserLimit(): bool
    {
        $plan = $this->currentPlan();
        $limit = $plan ? $plan->max_users : $this->max_users;
        return $this->users()->count() >= $limit;
    }

    public function hasReachedCameraLimit(): bool
    {
        $plan = $this->currentPlan();
        $limit = $plan ? $plan->max_cameras : $this->max_cameras;
        $count = $this->getCameraCount();

        return $count >= $limit;
    }

    public function getCameraCount(): int
    {
        return $this->cameras()->where('is_active', true)->count();
    }

    public function getStorageUsedGb(): float
    {
        $quota = $this->getQuota('storage_gb');
        return $quota ? $quota->current_usage : 0;
    }

    public function getStorageLimitGb(): int
    {
        $plan = $this->currentPlan();
        return $plan ? $plan->max_storage_gb : 50;
    }

    /**
     * Get all quotas as a structured array for the frontend.
     */
    public function getQuotaSummary(): array
    {
        $plan = $this->currentPlan();

        return [
            'cameras' => [
                'used' => $this->getQuota('cameras')?->current_usage ?? 0,
                'limit' => $plan?->max_cameras ?? $this->max_cameras,
                'percentage' => $this->getQuota('cameras')?->getPercentageUsed() ?? 0,
            ],
            'users' => [
                'used' => $this->users()->count(),
                'limit' => $plan?->max_users ?? $this->max_users,
                'percentage' => $plan ? round(($this->users()->count() / max(1, $plan->max_users)) * 100, 1) : 0,
            ],
            'storage_gb' => [
                'used' => $this->getQuota('storage_gb')?->current_usage ?? 0,
                'limit' => $plan?->max_storage_gb ?? 50,
                'percentage' => $this->getQuota('storage_gb')?->getPercentageUsed() ?? 0,
            ],
            'streams' => [
                'used' => $this->getQuota('streams')?->current_usage ?? 0,
                'limit' => $plan?->max_concurrent_streams ?? 4,
                'percentage' => $this->getQuota('streams')?->getPercentageUsed() ?? 0,
            ],
        ];
    }
}
