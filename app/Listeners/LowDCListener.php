<?php

namespace App\Listeners;

use App\Events\LowDCEvent;
use Illuminate\Support\Facades\Mail;
class LowDCListener
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
     * @param  LowDCEvent  $event
     * @return void
     */
    public function handle(LowDCEvent $event)
    {
        
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Low Voltage');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.'Is On Low Voltage');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Low Voltage');
            $messages->setBody('Your Vehicle No #'.$vehicle_id.'Is On High Voltage');
        });
      }
    }
}
