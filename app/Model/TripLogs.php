<?php

namespace App\Model;

use App\Model;
use App\Message\Error;


class TripLogs extends Model
{


    protected $primaryKey = "trip_log_id";
    protected $table = "trip_logs";
    protected $fillable = [
        'trip_id',
    ];

    public $timestamps = true;

    function add($data){

        try{
            $tripLog = parent::add($data);
            return $tripLog;
        }
        catch(\Exception $ex){
            Error::trigger("tripLog.add", [$ex->getMessage()]) ;
        }
    }
}
