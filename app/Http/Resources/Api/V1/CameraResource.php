<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CameraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->filterFields($request, [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'device_id' => $this->device_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'channel_number' => $this->channel_number,
            'channel_type' => $this->channel_type,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'stream_type' => $this->stream_type,
            'codec' => $this->codec,
            'resolution' => $this->resolution,
            'fps' => $this->fps,
            'has_ptz' => $this->has_ptz,
            'has_audio' => $this->has_audio,
            'is_active' => $this->is_active,
            'is_online' => $this->is_online,
            'is_recording' => $this->is_recording,
            'continuous_recording' => $this->continuous_recording,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'location' => $this->location,
            'description' => $this->description,
            'snapshot_url' => $this->snapshot_url,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Conditional includes
            'device' => $this->whenLoaded('device', fn () => new DeviceResource($this->device)),
            'tenant' => $this->whenLoaded('tenant', fn () => new TenantResource($this->tenant)),
            'active_stream' => $this->whenLoaded('activeStream', fn () => $this->activeStream ? new StreamResource($this->activeStream) : null),
            'latest_recording' => $this->whenLoaded('recordings', fn () => $this->recordings->first() ? new RecordingResource($this->recordings->first()) : null),
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
