<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Service as Validator;

class Service extends Model
{
    use Validator;

    protected $primaryKey = "service_id";
    protected $table = "fm_services";
    protected $fillable = [
        'title',
        'update_last_service',
        'vehicle_id',
        'status',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['created_by'=>0];

    function vehicle() {
        return $this->belongsTo('App\Model\Vehicle', 'vehicle_id', 'vehicle_id');
    }

    function service_types() {
        return $this->hasMany('App\Model\ServiceType', 'service_id', 'service_id');
    }

    /**
     * add services for specific vehicle.
     * $data conatins list of service ids to be added.
     * if some services are already added which are no more required for specific vehicle then remove them 
     */
    function addServicesForVehicle($data, $vehicle_id) {
        $alreadyInVehicle = Service::where('vehicle_id', $vehicle_id)->pluck('service_id')->toArray();

        //get extra services are already added, but should be removed now.
        $services_to_remove = array_diff($alreadyInVehicle, $data);

        try {
            Service::whereIn("service_id", $data)->update(["vehicle_id" => $vehicle_id, "status" => 1]);
            Service::whereIn("service_id", $services_to_remove)->update(["vehicle_id" => NULL, "status" => 9]);
        } catch(\Exception $ex) {
            Error::trigger("service.add", [$ex->getMessage()]);
        }
    }

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("service.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("service.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

       
        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("service.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $service_id) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("service.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("service.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $service_id);
        }
        catch(Exception $ex) {
            Error::trigger("service.change", [$ex->getMessage()]);
        }
    }
}
