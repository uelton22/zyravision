<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageSummaryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'used_gb' => $this->resource['used_gb'] ?? 0,
            'limit_gb' => $this->resource['limit_gb'] ?? 0,
            'percentage' => $this->resource['percentage'] ?? 0,
            'recordings_count' => $this->resource['recordings_count'] ?? 0,
            'segments_count' => $this->resource['segments_count'] ?? 0,
            'oldest_recording' => $this->resource['oldest_recording'] ?? null,
            'retention_days' => $this->resource['retention_days'] ?? null,
            'cameras' => $this->resource['cameras'] ?? [],
            'backends' => isset($this->resource['backends'])
                ? StorageBackendResource::collection($this->resource['backends'])
                : [],
        ];
    }
}
