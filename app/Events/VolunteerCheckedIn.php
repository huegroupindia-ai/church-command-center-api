<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VolunteerCheckedIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('volunteer.checkins'),
            new Channel('dashboard.updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VolunteerCheckedIn';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'volunteer_checked_in',
            'volunteer_name' => $this->data['volunteer_name'] ?? '',
            'department' => $this->data['department'] ?? '',
            'status' => $this->data['status'] ?? 'present',
            'service_name' => $this->data['service_name'] ?? '',
            'checked_in_at' => $this->data['checked_in_at'] ?? now()->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }
}
