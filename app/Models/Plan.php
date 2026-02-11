<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'billing_period',
        'is_active', 'is_featured', 'sort_order',
        // Quotas
        'max_cameras', 'max_users', 'max_storage_gb', 'retention_days',
        'max_concurrent_streams', 'max_resolution', 'max_fps',
        // Features
        'has_ai_detection', 'has_ai_recognition', 'has_analytics',
        'has_mobile_app', 'has_api_access', 'has_webrtc', 'has_ptz_control',
        'has_custom_alerts', 'has_export', 'has_multi_tenant',
        'has_sla', 'has_onpremise', 'has_priority_support', 'has_dedicated_support',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'max_cameras' => 'integer',
        'max_users' => 'integer',
        'max_storage_gb' => 'integer',
        'retention_days' => 'integer',
        'max_concurrent_streams' => 'integer',
        'max_fps' => 'integer',
        'has_ai_detection' => 'boolean',
        'has_ai_recognition' => 'boolean',
        'has_analytics' => 'boolean',
        'has_mobile_app' => 'boolean',
        'has_api_access' => 'boolean',
        'has_webrtc' => 'boolean',
        'has_ptz_control' => 'boolean',
        'has_custom_alerts' => 'boolean',
        'has_export' => 'boolean',
        'has_multi_tenant' => 'boolean',
        'has_sla' => 'boolean',
        'has_onpremise' => 'boolean',
        'has_priority_support' => 'boolean',
        'has_dedicated_support' => 'boolean',
        'metadata' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // ─── Helpers ──────────────────────────────────────────

    public function getFeatures(): array
    {
        $features = [];
        $featureMap = [
            'has_ai_detection' => 'Detecção IA',
            'has_ai_recognition' => 'Reconhecimento Facial',
            'has_analytics' => 'Analytics Avançado',
            'has_mobile_app' => 'App Mobile',
            'has_api_access' => 'Acesso API',
            'has_webrtc' => 'WebRTC (baixa latência)',
            'has_ptz_control' => 'Controle PTZ',
            'has_custom_alerts' => 'Alertas Personalizados',
            'has_export' => 'Exportação de Dados',
            'has_multi_tenant' => 'Multi-Tenant',
            'has_sla' => 'SLA Garantido',
            'has_onpremise' => 'Deploy On-Premise',
            'has_priority_support' => 'Suporte Prioritário',
            'has_dedicated_support' => 'Suporte Dedicado 24/7',
        ];

        foreach ($featureMap as $attr => $label) {
            if ($this->{$attr}) {
                $features[] = $label;
            }
        }

        return $features;
    }

    public function getFormattedPrice(): string
    {
        if ($this->price <= 0) {
            return 'Sob consulta';
        }

        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
}
