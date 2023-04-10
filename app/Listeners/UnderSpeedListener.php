<?php

namespace App\Listeners;

use App\Events\UnderSpeedEvent;
use Illuminate\Support\Facades\Mail;
class UnderSpeedListener
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
     * @param  UnderSpeedEvent  $event
     * @return void
     */
    public function handle(UnderSpeedEvent $event)
    {
      
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Lower Speed Limit');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Is Below Lower Speed Limit');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Lower Speed Limit');
            $messages->setBody('Your Vehicle No #'.$vehicle_id.'Is Above Lower Speed Limit Now');
        });
      }
    }
}
