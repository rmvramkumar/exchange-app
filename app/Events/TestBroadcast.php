<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TestBroadcast implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $timestamp;
    public $userId;

    public function __construct($message, $timestamp, $userId)
    {
        $this->message = $message;
        $this->timestamp = $timestamp;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('private-user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'TestEvent';
    }

    public function broadcastWith()
    {
        $payload = [
            'message' => $this->message,
            'timestamp' => $this->timestamp,
            'user_id' => $this->userId,
        ];
        return $payload;
    }
}
