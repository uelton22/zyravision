<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordingSegmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'camera_id' => $this->camera_id,
            'tenant_id' => $this->tenant_id,
            'status' => $this->status,
            'filename' => $this->filename,
            'file_size_bytes' => $this->file_size_bytes,
            'duration_seconds' => $this->duration_seconds,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'codec' => $this->codec,
            'resolution' => $this->resolution,
            'fps' => $this->fps,
            'thumbnail_path' => $this->thumbnail_path,
            'created_at' => $this->created_at->toISOString(),

            'camera' => $this->whenLoaded('camera', fn () => new CameraResource($this->camera)),
        ];
    }
}
