<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            'tenant' => $this->whenLoaded('tenant', fn () => new TenantResource($this->tenant)),
            'roles' => $this->roles->pluck('name'),
            'permissions' => $this->getAllPermissions()->pluck('name'),

            'quotas' => $this->when($this->relationLoaded('tenant'), function () {
                $tenant = $this->tenant;
                if (! $tenant) {
                    return null;
                }

                return [
                    'cameras' => [
                        'used' => $tenant->cameras()->count(),
                        'limit' => $tenant->max_cameras,
                    ],
                    'users' => [
                        'used' => $tenant->users()->count(),
                        'limit' => $tenant->max_users,
                    ],
                ];
            }),
        ];
    }
}
