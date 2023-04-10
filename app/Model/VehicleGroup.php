<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\VehicleGroup as Validator;

class VehicleGroup extends Model
{
    use Validator;

    protected $primaryKey = "vehicle_group_id";
    protected $table = "vehicle_groups";
    protected $fillable = ['title', 'parent_id', 'status', 'created_by','geofence_id'];
    protected $attributes = ['status' => 1, 'created_by'=>0];



    function geo_fence() {
		return $this->belongsTo('App\Model\GeoFence', 'geofence_id','id')->select(['id','name']);
	}

    function vehicles_in_vehicle_groups() {
		return $this->hasMany('App\Model\VehiclesInVehicleGroup', 'vehicle_group_id','vehicle_group_id');
	}


    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("vehiclegroup.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("vehiclegroup.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}


        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("vehiclegroup.add", [$ex->getMessage()]);
        }
    }

    function change($data, $vehicle_group_id) {

       
        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("vehiclegroup.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("vehiclegroup.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        
        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $vehicle_group_id);
        }
        catch(Exception $ex) {
            Error::trigger("vehiclegroup.change", [$ex->getMessage()]);
        }
    }
}
