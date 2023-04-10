<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DB;
use Illuminate\Support\Facades\File; // File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot()
    {
        //  try {
        //   DB::listen(function($query) {
        //     // if($query->time > 1000){
        //       $sql = str_replace(array('%', '?'), array('%%', '%s'), $query->sql);
        //       $sql = vsprintf($sql, $query->bindings);
        //       File::append(
        //         storage_path('/queries/'.date('Y-m-d').'_query.log'),
        //         '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL . $sql . '( ' . $query->time . 'ms'. ' )' . PHP_EOL . PHP_EOL
        //       );
        //     // }
        //   });
        // } catch (\Exception $e) {
        
        // }
        // // \Illuminate\Support\Facades\URL::forceScheme('https');

    }

    
}
