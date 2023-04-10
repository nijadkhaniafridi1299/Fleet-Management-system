<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Sensor as Validator;

class Sensor extends Model
{
    use Validator;

    protected $primaryKey = "sensor_id";
    protected $table = "fm_sensors";
    protected $fillable = [
        'title',
        'show_in_tooltip',
        'show_last_change_time',
        'is_fixed',
        'result_type',
        'sensor_1_text',
        'sensor_0_text',
        'sensor_type_id',
        'parameter_id',
        'device_id',
        'color',
        'icon',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['sensor_type_id' => 0, 'parameter_id' => 0, 'created_by'=>0];

    function sensor_type() {
        return $this->belongsTo('App\Model\SensorType', 'sensor_type_id', 'sensor_type_id');
    }

     function parameter() {
        return $this->belongsTo('App\Model\Parameter', 'parameter_id', 'parameter_id');
    }

    /**
     * add sensors for specific vehicle device.
     * $data contains list of sensors ids to be added.
     * if some sensors are already added which are no more required then specific sensor will be removed from that device
     */
    function addSensorsForDevice($data, $device_id) {
        $alreadyInDevice = Sensor::where('device_id', $device_id)->pluck('sensor_id')->toArray();

        //get extra services are already added, but should be removed now.
        $sensors_to_remove = array_diff($alreadyInDevice, $data);

        try {
            
            Sensor::whereIn("sensor_id", $data)->update(["device_id" => $device_id, "status" => 1]);
            Sensor::whereIn("sensor_id", $sensors_to_remove)->update(["device_id" => NULL, "status" => 9]);
            
        } catch(\Exception $ex) {
            Error::trigger("sensor.add", [$ex->getMessage()]);
        }
    }

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
