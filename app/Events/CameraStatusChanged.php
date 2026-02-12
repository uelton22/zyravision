<?php

namespace App\Events;

use App\Models\Camera;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CameraStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Camera $camera,
        public string $changeType = 'status',
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->camera->tenant_id),
            new PrivateChannel('camera.' . $this->camera->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'camera.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'camera_id' => $this->camera->id,
            'camera_name' => $this->camera->name,
            'is_online' => $this->camera->is_online,
            'is_recording' => $this->camera->is_recording,
            'is_active' => $this->camera->is_active,
            'change_type' => $this->changeType,
            'changed_at' => now()->toISOString(),
        ];
    }
}
