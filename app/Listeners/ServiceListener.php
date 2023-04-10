<?php

namespace App\Listeners;

use App\Events\ServiceEvent;
use Illuminate\Support\Facades\Mail;
class ServiceListener
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
     * @param  ServiceEvent  $event
     * @return void
     */
    public function handle(ServiceEvent $event)
    {
       
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Service Pending');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Service Is Pending');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Service Completed');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Service Is Completed');
        });
      }
    }
}
