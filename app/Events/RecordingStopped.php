<?php

namespace App\Events;

use App\Models\Camera;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordingStopped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Camera $camera,
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
        return 'recording.stopped';
    }

    public function broadcastWith(): array
    {
        return [
            'camera_id' => $this->camera->id,
            'camera_name' => $this->camera->name,
            'stopped_at' => now()->toISOString(),
        ];
    }
}
