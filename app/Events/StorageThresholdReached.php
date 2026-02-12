<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StorageThresholdReached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public float $usagePercentage,
        public float $usedGb,
        public float $limitGb,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenant->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'storage.threshold';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'usage_percentage' => $this->usagePercentage,
            'used_gb' => $this->usedGb,
            'limit_gb' => $this->limitGb,
            'triggered_at' => now()->toISOString(),
        ];
    }
}
