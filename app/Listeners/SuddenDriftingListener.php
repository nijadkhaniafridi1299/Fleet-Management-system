<?php

namespace App\Listeners;

use App\Events\SuddenDriftingEvent;
use Illuminate\Support\Facades\Mail;
class SuddenDriftingListener
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
     * @param  SuddenDriftingEvent  $event
     * @return void
     */
    public function handle(SuddenDriftingEvent $event)
    {
      
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Sudden Drifting');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Suddenly Drifts');
        });

  
    }
}
