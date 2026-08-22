<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceReadinessUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('dashboard.updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ServiceReadinessUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'service_readiness_updated',
            'service_id' => $this->data['service_id'] ?? null,
            'service_name' => $this->data['service_name'] ?? '',
            'readiness_score' => $this->data['readiness_score'] ?? 0,
            'departments' => $this->data['departments'] ?? [],
            'timestamp' => now()->toISOString(),
        ];
    }
}
