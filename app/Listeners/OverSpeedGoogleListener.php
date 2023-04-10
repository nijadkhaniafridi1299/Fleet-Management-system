<?php

namespace App\Listeners;

use App\Events\OverSpeedGoogleEvent;
use Illuminate\Support\Facades\Mail;

class OverSpeedGoogleListener
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
     * @param  OverSpeedGoogleEvent  $event
     * @return void
     */
    public function handle(OverSpeedGoogleEvent $event)
    {
     
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Over Speed Google Event');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Is OverSpeeding Google Speed Limit');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Normal Speed');
            $messages->setBody('Your Vehicle No #'.$vehicle_id.'Is On Normal Google Speed Limit');
        });
      }
    }
}
