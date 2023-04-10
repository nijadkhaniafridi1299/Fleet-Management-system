<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\UpdateDeviceStatus::class,
        Commands\UpdateVehicleLocation::class,
        Commands\SetAreaName::class,
        \Laravelista\LumenVendorPublish\VendorPublishCommand::class
    ];


    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
  
        $schedule->command('VehicleLoc')->everyMinute()->emailOutputTo('saadabbasi263@gmail.com');
        $schedule->command('UpdateDeviceStatus')->everyMinute()->emailOutputTo('saadabbasi263@gmail.com');
        $schedule->command('SetAreaName')->everyMinute()->emailOutputTo('saadabbasi263@gmail.com');
    }
}
