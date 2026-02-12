<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'camera_id' => $this->camera_id,
            'tenant_id' => $this->tenant_id,
            'stream_id' => $this->stream_id,
            'status' => $this->status,
            'storage_path' => $this->storage_path,
            'file_size_bytes' => $this->file_size_bytes,
            'file_size_human' => $this->formatFileSize($this->file_size_bytes),
            'duration_seconds' => $this->duration_seconds,
            'duration_human' => $this->formatDuration($this->duration_seconds),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'codec' => $this->codec,
            'resolution' => $this->resolution,
            'fps' => $this->fps,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            'camera' => $this->whenLoaded('camera', fn () => new CameraResource($this->camera)),
            'stream' => $this->whenLoaded('stream', fn () => new StreamResource($this->stream)),
            'segments' => RecordingSegmentResource::collection($this->whenLoaded('segments')),

            'playback_url' => $this->when($this->status === 'completed', fn () => "/api/v1/recordings/{$this->id}/stream"),
            'download_url' => $this->when($this->status === 'completed', fn () => "/api/v1/recordings/{$this->id}/download"),
        ];
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return number_format($bytes / 1024, 0) . ' KB';
    }

    protected function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $secs);
        }

        return sprintf('%dm %02ds', $minutes, $secs);
    }
}
