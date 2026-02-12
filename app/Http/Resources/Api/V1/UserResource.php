<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->filterFields($request, [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'tenant_id' => $this->tenant_id,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Conditional includes
            'tenant' => $this->whenLoaded('tenant', fn () => new TenantResource($this->tenant)),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->when(
                $this->relationLoaded('permissions') || $request->has('include') && str_contains($request->include, 'permissions'),
                fn () => $this->getAllPermissions()->pluck('name')
            ),
        ]);
    }

    protected function filterFields(Request $request, array $data): array
    {
        if (! $request->has('fields')) {
            return $data;
        }

        $fields = explode(',', $request->query('fields'));
        $fields[] = 'id';

        return array_intersect_key($data, array_flip($fields));
    }
}
