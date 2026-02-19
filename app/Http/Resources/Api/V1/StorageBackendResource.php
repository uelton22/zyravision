<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageBackendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'driver' => $this->driver,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'priority' => $this->priority,
            'health_status' => $this->health_status,
            'last_health_check_at' => $this->last_health_check_at?->toISOString(),
            'health_check_latency_ms' => $this->health_check_latency_ms,
            'health_check_message' => $this->health_check_message,
            'total_capacity_gb' => $this->total_capacity_gb,
            'used_capacity_gb' => $this->used_capacity_gb,
            'usage_percentage' => $this->total_capacity_gb > 0
                ? round(($this->used_capacity_gb / $this->total_capacity_gb) * 100, 1)
                : 0,
            'total_files' => $this->total_files,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            // Config is intentionally excluded (encrypted credentials)
        ];
    }
}
