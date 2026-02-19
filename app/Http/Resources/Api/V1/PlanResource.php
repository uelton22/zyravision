<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'billing_period' => $this->billing_period,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // Limits
            'limits' => [
                'max_cameras' => $this->max_cameras,
                'max_users' => $this->max_users,
                'max_storage_gb' => $this->max_storage_gb,
                'retention_days' => $this->retention_days,
                'max_concurrent_streams' => $this->max_concurrent_streams,
                'max_resolution' => $this->max_resolution,
                'max_fps' => $this->max_fps,
            ],

            // Features
            'features' => [
                'ai_detection' => $this->has_ai_detection,
                'ai_recognition' => $this->has_ai_recognition,
                'analytics' => $this->has_analytics,
                'mobile_app' => $this->has_mobile_app,
                'api_access' => $this->has_api_access,
                'webrtc' => $this->has_webrtc,
                'ptz_control' => $this->has_ptz_control,
                'custom_alerts' => $this->has_custom_alerts,
                'export' => $this->has_export,
                'multi_tenant' => $this->has_multi_tenant,
                'sla' => $this->has_sla,
                'onpremise' => $this->has_onpremise,
                'priority_support' => $this->has_priority_support,
                'dedicated_support' => $this->has_dedicated_support,
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
