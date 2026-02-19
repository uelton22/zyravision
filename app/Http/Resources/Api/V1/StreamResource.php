<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StreamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'camera_id' => $this->camera_id,
            'tenant_id' => $this->tenant_id,
            'status' => $this->status,
            'mediamtx_path' => $this->mediamtx_path,
            'started_at' => $this->started_at?->toISOString(),
            'stopped_at' => $this->stopped_at?->toISOString(),
            'last_health_check' => $this->last_health_check?->toISOString(),
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Player URLs (only when stream is active)
            'urls' => $this->when($this->status === 'active', [
                'hls' => $this->hls_url,
                'webrtc' => $this->webrtc_url,
                'rtsp' => $this->rtsp_input_url,
            ]),

            'camera' => $this->whenLoaded('camera', fn () => new CameraResource($this->camera)),
        ];
    }
}
