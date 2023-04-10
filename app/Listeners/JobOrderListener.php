<?php

namespace App\Listeners;

use App\Events\JobOrderEvent;
use Illuminate\Support\Facades\Mail;
class JobOrderListener
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
     * @param  JobOrderEvent  $event
     * @return void
     */
    public function handle(JobOrderEvent $event)
    {
      
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;
   
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Job Order');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Job Order Updated');
        });

  
    }
}
