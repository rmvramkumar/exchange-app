<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderMatched implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;
    public $trade;
    public $buyerId;
    public $sellerId;

    public function __construct($trade, $buyerId, $sellerId)
    {
        $this->trade = $trade;
        $this->buyerId = $buyerId;
        $this->sellerId = $sellerId;
    }

    public function broadcastOn()
    {
        // broadcast to both users private channels
        // Use full 'private-user.{id}' format for Pusher backend
        $channels = [
            new PrivateChannel('user.' . $this->buyerId),
            new PrivateChannel('user.' . $this->sellerId),
        ];
        return $channels;
    }

    public function broadcastWith()
    {
        $payload = [
            'trade' => $this->trade->toArray(),
        ];
        return $payload;
    }
}
