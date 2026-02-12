<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileDashboardResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'stats' => $this->resource['stats'] ?? [],
            'cameras' => isset($this->resource['cameras'])
                ? CameraResource::collection($this->resource['cameras'])
                : [],
            'recent_events' => $this->resource['recent_events'] ?? [],
            'storage' => $this->resource['storage'] ?? null,
            'synced_at' => now()->toISOString(),
        ];
    }
}
