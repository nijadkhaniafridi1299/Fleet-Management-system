<?php

namespace App\Listeners;
use App\Events\LiveLocationEvent as LiveLocationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LiveLocationListener
{
  
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        // dd('Listner Is Active');
    }


    public function handle(LiveLocationEvent $event)
    {
        // dd('Listner Is Active');
        echo('connected through channel');
    }
}
