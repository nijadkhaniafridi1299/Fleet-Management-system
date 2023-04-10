<?php

namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LiveLocationEvent implements ShouldBroadcast
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $message;
    public function __construct($message)
    {
        // dd('Done');
        $this->message=$message;
        // return new Channel('LiveLocationEvent');
  

    }
    public function broadcastOn()
{

    return new PrivateChannel('LiveLocationEvent');
  

}
public function broadcastWith()
{
    return ["message" => 'This Is Live Location'];
}
public function broadcastAs()
{
    return 'latlong';
}
}
