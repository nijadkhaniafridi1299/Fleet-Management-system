<?php

namespace App\Listeners;

use App\Events\SuddenAccelerationEvent;
use Illuminate\Support\Facades\Mail;
class SuddenAccelerationListener
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
     * @param  SuddenAccelerationEvent  $event
     * @return void
     */
    public function handle(SuddenAccelerationEvent $event)
    {
         
        $dataarray = (array) $event;
        $vehicle_id=$dataarray['data']->vehicle_id;
        $event_type_id=$dataarray['data']->event_type_id;
        $event_status=$dataarray['data']->event_status;
        $email=$dataarray['data']->email;

       if($event_status == 0){

       
      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Sudden Acceleration');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Suddenly Accelerated');
        });

      }
      else
      {


      
        Mail::send([],[], function ($messages) use($email,$vehicle_id) {
            $messages->to($email);
            $messages->subject('Sudden De-Acceleration');
            $messages->setBody('Your Vehicle No # '.$vehicle_id.' Suddenly De-Accelerated');
        });
      }
    }
}
