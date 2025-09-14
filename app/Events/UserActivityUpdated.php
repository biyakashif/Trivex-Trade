<?php

// namespace App\Events;

// use Illuminate\Broadcasting\Channel;
// use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Broadcasting\PrivateChannel;
// use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
// use Illuminate\Foundation\Events\Dispatchable;
// use Illuminate\Queue\SerializesModels;

// class UserActivityUpdated implements ShouldBroadcast
// {
//     use Dispatchable, InteractsWithSockets, SerializesModels;

//     public $userId;
//     public $isOnline;

//     public function __construct($userId, $isOnline)
//     {
//         $this->userId = $userId;
//         $this->isOnline = $isOnline;
//     }

//     public function broadcastOn()
//     {
//         return new PrivateChannel('admin.users');
//     }

//     public function broadcastAs()
//     {
//         return 'user.activity.updated';
//     }

//     public function broadcastWith()
//     {
//         return [
//             'user_id' => $this->userId,
//             'is_online' => $this->isOnline,
//         ];
//     }
// }


namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public bool $isOnline;

    public function __construct($userId, $isOnline)
    {
        $this->userId = $userId;
        $this->isOnline = $isOnline;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.status');
    }

    public function broadcastAs(): string
    {
        return 'user.activity.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'is_online' => $this->isOnline,
        ];
    }
}
