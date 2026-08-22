<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EquipmentAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('equipment.alerts'),
            new Channel('dashboard.updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'EquipmentAlert';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'equipment_alert',
            'equipment_id' => $this->data['equipment_id'] ?? null,
            'equipment_name' => $this->data['equipment_name'] ?? '',
            'alert_type' => $this->data['alert_type'] ?? 'fault_report',
            'severity' => $this->data['severity'] ?? 'medium',
            'message' => $this->data['message'] ?? '',
            'reported_by' => $this->data['reported_by'] ?? '',
            'timestamp' => now()->toISOString(),
        ];
    }
}
