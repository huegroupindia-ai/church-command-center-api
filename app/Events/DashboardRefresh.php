<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardRefresh implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?int $userId = null,
        public string $reason = '',
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('dashboard.updates'),
        ];

        if ($this->userId) {
            $channels[] = new Channel('private.user.' . $this->userId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'DashboardRefresh';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'dashboard_refresh',
            'reason' => $this->reason,
            'timestamp' => now()->toISOString(),
        ];
    }
}
