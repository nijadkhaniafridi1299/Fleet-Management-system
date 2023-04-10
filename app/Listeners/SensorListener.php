<?php

namespace App\Listeners;

use App\Events\SensorEvent;
use Illuminate\Support\Facades\Mail;
class SensorListener
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
     * @param  SensorEvent  $event
     * @return void
     */
    public function handle(SensorEvent $event)
    {
     
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Sensor Disconnected');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Sensor Is Disconnected');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Sensor Connected');
            $messages->setBody('Your Vehicle No #'.$vehicle_id.'Sensor Is Connected');
        });
      }
    }
}
