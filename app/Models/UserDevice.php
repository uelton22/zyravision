<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'device_id',
        'device_name',
        'platform',
        'app_version',
        'os_version',
        'push_token',
        'push_provider',
        'is_active',
        'last_active_at',
        'revoked_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('revoked_at');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ─── Helpers ──────────────────────────────────────────

    public function revoke(): void
    {
        $this->update([
            'is_active' => false,
            'revoked_at' => now(),
            'push_token' => null,
        ]);
    }

    public function touchActivity(): bool
    {
        $this->last_active_at = now();

        return $this->save();
    }
}
