<?php

namespace App\Events;

use App\Models\Camera;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordingStopped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Camera $camera,
    ) {}
}
