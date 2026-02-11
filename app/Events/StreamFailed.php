<?php

namespace App\Events;

use App\Models\Stream;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Stream $stream,
        public ?string $reason = null,
    ) {}
}
