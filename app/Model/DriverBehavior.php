<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\DriverBehavior as Validator;

class DriverBehavior extends Model
{
    use Validator;

    protected $primaryKey = "driver_behavior_id";
    protected $table = "fm_driver_behaviors";
    protected $fillable = [
        'title',
        'penalty',
        'min_value',
        'max_value',
        'min_speed',
        'max_speed',
        'min_duration',
        'max_duration',
        'sensor_id',
        'vehicle_id',
        'status',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['created_by'=>0];

    function vehicle() {
        return $this->belongsTo('\App\Model\Vehicle', 'vehicle_id', 'vehicle_id');
    }

    function sensor() {
        return $this->belongsTo('App\Model\Sensor', 'sensor_id', 'sensor_id');
    }

    function addDriverBehaviorForVehicle($data, $vehicle_id) {
        $alreadyInVehicle = DriverBehavior::where('vehicle_id', $vehicle_id)->pluck('driver_behavior_id')->toArray();

        //get extra behaviors which are already added, but should be removed now.
        $behaviors_to_remove = array_diff($alreadyInVehicle, $data);

        try {
            DriverBehavior::whereIn("driver_behavior_id", $data)->update(["vehicle_id" => $vehicle_id, "status" => 1]);
            DriverBehavior::whereIn("driver_behavior_id", $behaviors_to_remove)->update(["vehicle_id" => NULL, "status" => 9]);
        } catch(\Exception $ex) {
            Error::trigger("driverbehavior.add", [$ex->getMessage()]);
        }
    }

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("driverbehavior.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("driverbehavior.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("driverbehavior.add", [$ex->getMessage()]);
        }
    }

    function change($data, $driver_behavior_id) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("driverbehavior.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("driverbehavior.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $driver_behavior_id);
        }
        catch(Exception $ex) {
            Error::trigger("driverbehavior.change", [$ex->getMessage()]) ;
        }
    }
}
