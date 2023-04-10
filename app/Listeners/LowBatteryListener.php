<?php

namespace App\Listeners;

use App\Events\LowBatteryEvent;
use Illuminate\Support\Facades\Mail;
class LowBatteryListener
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
     * @param  LowBatteryEvent  $event
     * @return void
     */
    public function handle(LowBatteryEvent $event)
    {
      
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Low Battery');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.'Is On Low Battery');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Battery Installed');
            $messages->setBody('Your Vehicle No #'.$vehicle_id.'Is On Required Battery');
        });
      }
    }
}
