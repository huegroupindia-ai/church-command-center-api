<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChecklistUpdated implements ShouldBroadcast
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
        return 'ChecklistUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'checklist_updated',
            'checklist_id' => $this->data['checklist_id'] ?? null,
            'service_id' => $this->data['service_id'] ?? null,
            'item_name' => $this->data['item_name'] ?? '',
            'completed_by' => $this->data['completed_by'] ?? '',
            'completion_percentage' => $this->data['completion_percentage'] ?? 0,
            'timestamp' => now()->toISOString(),
        ];
    }
}
