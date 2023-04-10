<?php

namespace App\Listeners;

use App\Events\IdleEvent;
use Illuminate\Support\Facades\Mail;
class IdleListener
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
     * @param  IdleEvent  $event
     * @return void
     */
    public function handle(IdleEvent $event)
    {
       
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Vehicle Idle');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Is In Idle Position');
        });

   
    }
}
