<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'firmware_version' => $this->firmware_version,
            'address_type' => $this->address_type,
            'address' => $this->address,
            'port' => $this->port,
            'http_port' => $this->http_port,
            'transport' => $this->transport,
            'total_channels' => $this->total_channels,
            'active_channels' => $this->active_channels,
            'onvif_supported' => $this->onvif_supported,
            'is_active' => $this->is_active,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'location' => $this->location,
            'serial_number' => $this->serial_number,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'cameras' => CameraResource::collection($this->whenLoaded('cameras')),
            'cameras_count' => $this->when($this->cameras_count !== null, $this->cameras_count),
        ];
    }
}
