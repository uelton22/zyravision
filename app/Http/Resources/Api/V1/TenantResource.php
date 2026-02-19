<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'logo_url' => $this->logo_url,
            'is_active' => $this->is_active,
            'max_cameras' => $this->max_cameras,
            'max_users' => $this->max_users,
            'storage_retention_days' => $this->storage_retention_days,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'active_subscription' => $this->whenLoaded('activeSubscription'),
            'plan' => $this->whenLoaded('activeSubscription', function () {
                $sub = $this->activeSubscription;
                return $sub?->plan ? new PlanResource($sub->plan) : null;
            }),
        ];
    }
}
