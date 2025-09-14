<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnlineStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $isOnline;

    public function __construct($userId, $isOnline)
    {
        $this->userId = $userId;
        $this->isOnline = $isOnline;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('online-users');
    }

    public function broadcastAs()
    {
        return 'user.online.status';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'is_online' => $this->isOnline,
        ];
    }
}