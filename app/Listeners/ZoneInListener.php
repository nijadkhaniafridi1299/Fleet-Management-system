<?php

namespace App\Listeners;

use App\Events\ZoneInEvent;
use Illuminate\Support\Facades\Mail;
class ZoneInListener
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
     * @param  ZoneInEvent  $event
     * @return void
     */
    public function handle(ZoneInEvent $event)
    {
      
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;


       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Vehicle In Zone');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Is In The Zone');
        });

      
    }
}
