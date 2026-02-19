<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'device_name' => $this->device_name,
            'platform' => $this->platform,
            'app_version' => $this->app_version,
            'os_version' => $this->os_version,
            'is_active' => $this->is_active,
            'has_push_token' => ! empty($this->push_token),
            'push_provider' => $this->push_provider,
            'last_active_at' => $this->last_active_at?->toISOString(),
            'revoked_at' => $this->revoked_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
