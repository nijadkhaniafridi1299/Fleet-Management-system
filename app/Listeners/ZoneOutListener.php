<?php

namespace App\Listeners;

use App\Events\ZoneOutEvent;
use Illuminate\Support\Facades\Mail;
class ZoneOutListener
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
     * @param  ZoneOutEvent  $event
     * @return void
     */
    public function handle(ZoneOutEvent $event)
    {
     
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;


       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Zone Out');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Is Out Of Zone Now');
        });

  
    }
}
