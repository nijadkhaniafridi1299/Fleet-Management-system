<?php

namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MovingEngineOffEvent 
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $data;


    public function __construct($data)
    {
       
        $this->data=$data;
        
        
        // return new Channel('MovingEngineOffEvent');
  

    }
    public function broadcastOn()
{
    // dd('done');
    // return new Channel('LiveLocationEvent');
  

}
public function broadcastAs()
{
    // return 'Server';
}
}
