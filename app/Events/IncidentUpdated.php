<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('incidents'),
            new Channel('dashboard.updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'IncidentUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'incident_updated',
            'incident_id' => $this->data['incident_id'] ?? null,
            'title' => $this->data['title'] ?? '',
            'severity' => $this->data['severity'] ?? 'low',
            'status' => $this->data['status'] ?? 'open',
            'reported_by' => $this->data['reported_by'] ?? '',
            'timestamp' => now()->toISOString(),
        ];
    }
}
