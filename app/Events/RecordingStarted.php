<?php

namespace App\Events;

use App\Models\Recording;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordingStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Recording $recording,
    ) {}
}
