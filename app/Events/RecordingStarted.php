<?php

namespace App\Events;

use App\Models\Recording;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordingStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Recording $recording,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->recording->tenant_id),
            new PrivateChannel('camera.' . $this->recording->camera_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'recording.started';
    }

    public function broadcastWith(): array
    {
        return [
            'recording_id' => $this->recording->id,
            'camera_id' => $this->recording->camera_id,
            'started_at' => $this->recording->started_at?->toISOString(),
        ];
    }
}
