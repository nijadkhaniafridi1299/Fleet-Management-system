<?php

namespace App\Listeners;
use App\Events\MovingEngineOffEvent as MovingEngineOffEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail as Mail;

class MovingEngineOffListener
{
  
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
       
    }


    public function handle(MovingEngineOffEvent $event)
    {
        
    
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

      if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Vehicle Stopped');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Engine is Off');
        });

      }
      else
      {

      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Vehicle Moving');
            $messages->setBody('Your Vehicle No  # '.$vehicle_id.' Is On The Move');
        });
      }
   

        
    }
}
