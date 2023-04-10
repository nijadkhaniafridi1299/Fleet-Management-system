<?php

namespace App\Listeners;
use Illuminate\Support\Facades\Mail;
use App\Events\PowercutEvent;

class PowercutListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PowercutEvent  $event
     * @return void
     */
    public function handle(PowercutEvent $event)

    {
        
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Power Cut');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.'Power Is Cut');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Power Cut');
            $messages->setBody('Your Vehicle No #'.$vehicle_id.' Power Is Connected');
        });
      }
    }
}
