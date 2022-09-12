<?php

namespace App\Events;

use App\Models\Reserva;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservaCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Reserva $pago
     */
    public $reserva;

    /**
     * @var integer $userId
     */
    public $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($reserva, $userId)
    {
        $this->reserva = $reserva;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
