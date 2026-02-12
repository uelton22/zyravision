<?php

namespace App\Events;

use App\Models\Stream;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Stream $stream,
        public ?string $reason = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->stream->tenant_id),
            new PrivateChannel('camera.' . $this->stream->camera_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stream.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'stream_id' => $this->stream->id,
            'camera_id' => $this->stream->camera_id,
            'status' => $this->stream->status,
            'error_message' => $this->reason ?? $this->stream->error_message,
        ];
    }
}
