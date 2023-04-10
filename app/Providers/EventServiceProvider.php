<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\LiveLocationEvent::class => [
            \App\Listeners\LiveLocationListener::class,
        ],

        \App\Events\MovingEngineOffEvent::class => [
            \App\Listeners\MovingEngineOffListener::class,
        ],
        
        \App\Events\PowercutEvent::class => [
            \App\Listeners\PowercutListener::class,
        ],
        \App\Events\GPSAntennaCutEvent::class => [
            \App\Listeners\GPSAntennaCutListener::class,
        ],
        \App\Events\LowDCEvent::class => [
            \App\Listeners\LowDCListener::class,
        ],
        \App\Events\LowBatteryEvent::class => [
            \App\Listeners\LowBatteryListener::class,
        ],
        \App\Events\NoConnectionEvent::class => [
            \App\Listeners\NoConnectionListener::class,
        ],
        \App\Events\OverSpeedEvent::class => [
            \App\Listeners\OverSpeedListener::class,
        ],
        \App\Events\OverSpeedGoogleEvent::class => [
            \App\Listeners\OverSpeedGoogleListener::class,
        ],
        \App\Events\UnderSpeedEvent::class => [
            \App\Listeners\UnderSpeedListener::class,
        ],
        \App\Events\SuddenAccelerationEvent::class => [
            \App\Listeners\SuddenAccelerationListener::class,
        ],
        \App\Events\SuddenBrakingEvent::class => [
            \App\Listeners\SuddenBrakingListener::class,
        ],
        \App\Events\SuddenDriftingEvent::class => [
            \App\Listeners\SuddenDriftingListener::class,
        ],
        \App\Events\SensorEvent::class => [
            \App\Listeners\SensorListener::class,
        ],
        \App\Events\ServiceEvent::class => [
            \App\Listeners\ServiceListener::class,
        ],
        \App\Events\ZoneInEvent::class => [
            \App\Listeners\ZoneInListener::class,
        ],
        \App\Events\ZoneOutEvent::class => [
            \App\Listeners\ZoneOutListener::class,
        ],
        \App\Events\StopEvent::class => [
            \App\Listeners\StopListener::class,
        ],
        \App\Events\IdleEvent::class => [
            \App\Listeners\IdleListener::class,
        ],
        \App\Events\JobOrderEvent::class => [
            \App\Listeners\JobOrderListener::class,
        ],





        
       
    ];
}
