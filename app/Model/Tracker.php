<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
class Tracker extends Model
{

    protected $primaryKey = "id";
    protected $table = "sensor_data";
    protected $fillable = [
        'Ignition',
        'Movement',
        'Speed',
        'Total Odometer',
        'IMEI',
        'iButton',
        'RFID',
     
    ];

    


    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("sensor.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $sensor_id){

        try{
            return parent::change($data, $sensor_id);
        }
        catch(Exception $ex){
            Error::trigger("sensor.change", [$ex->getMessage()]);
        }
    }
}
