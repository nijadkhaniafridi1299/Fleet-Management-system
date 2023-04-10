<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\VehicleType as Validator;

class VehicleType extends Model
{
    use Validator;

    protected $primaryKey = "vehicle_type_id";
    protected $table = "vehicle_types";
    protected $fillable = ['vehicle_type', 'created_by', 'status', 'capacity', 'weight', 'volume', 'erp_id','svg_icon_path'];
    protected $attributes = ['vehicle_type_meta'=>'{}', 'status' => 1, 'created_by'=>0, 'capacity' => '[]'];

    function add($data){

        $status = $data['status'];
        $data = $data['vehicleType'];
        $data['vehicle_type']['en'] = cleanNameString($data['vehicle_type']['en']);

		if (!isset($data['vehicle_type']['en']) || $data['vehicle_type']['en'] == '') {
			Error::trigger("vehicletype.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['vehicle_type']['ar'] = cleanNameString($data['vehicle_type']['ar']);

		if (!isset($data['vehicle_type']['ar']) || $data['vehicle_type']['ar'] == '') {
			Error::trigger("vehicletype.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}


        $data['vehicle_type'] = array_filter($data['vehicle_type']);
        $data['vehicle_type'] = json_encode($data['vehicle_type'], JSON_UNESCAPED_UNICODE);

        if (!isset($data['vehicle_type_meta'])) {
            $data['vehicle_type_meta'] = [];
        }

        $data['vehicle_type_meta'] = array_filter($data['vehicle_type_meta']);
        $data['vehicle_type_meta'] = json_encode($data['vehicle_type_meta'], JSON_UNESCAPED_UNICODE);
        $data['status'] = $status;


        //$data['capacity'] = array_filter($data['capacity']);

        // if (isset($data['capacity']) && count($data['capacity']) == 0) {
        //     Error::trigger("vehicletype.add", ["Please Specify capacity for Vehicle."]);
		// 	return false;
        // }

        //$data['capacity'] = json_encode($data['capacity'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);

        }
        catch(\Exception $ex) {
            Error::trigger("vehicletype.add", [$ex->getMessage()]);
        }
    }

    function change($data, $vehicle_type_id) {

        $status = $data['status'];
        $data = $data['vehicleType'];
        $data['vehicle_type']['en'] = cleanNameString($data['vehicle_type']['en']);

		if (!isset($data['vehicle_type']['en']) || $data['vehicle_type']['en'] == '') {
			Error::trigger("vehicletype.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['vehicle_type']['ar'] = cleanNameString($data['vehicle_type']['ar']);

		if (!isset($data['vehicle_type']['ar']) || $data['vehicle_type']['ar'] == '') {
			Error::trigger("vehicletype.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        
        $data['vehicle_type'] = array_filter($data['vehicle_type']);
        $data['vehicle_type'] = json_encode($data['vehicle_type'], JSON_UNESCAPED_UNICODE);

        if (!isset($data['vehicle_type_meta'])) {
            $data['vehicle_type_meta'] = [];
        }

        $data['vehicle_type_meta'] = array_filter($data['vehicle_type_meta']);
        $data['vehicle_type_meta'] = json_encode($data['vehicle_type_meta'], JSON_UNESCAPED_UNICODE);

        $data['status'] = $status;
        

        // $data['capacity'] = array_filter($data['capacity']);

        // if (isset($data['capacity']) && count($data['capacity']) == 0) {
        //     Error::trigger("vehicletype.change", ["Please Specify capacity for Vehicle."]);
		// 	return false;
        // }

        // $data['capacity'] = json_encode($data['capacity'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $vehicle_type_id);
        }
        catch(Exception $ex) {
            Error::trigger("vehicletype.change", [$ex->getMessage()]);
        }
    }

    function upload($file, $entity = 'product') {
        
        $public_path = base_path('public');
       
		$name = $file->getClientOriginalName();
		$name = str_replace("." . $file->getClientOriginalExtension(), "", $file->getClientOriginalName());

		$_name = preg_replace('#[^A-Za-z0-9_\-]#', '-', $name);

		$counter = '';
		$path = $public_path . '/images/' . $entity . '/';

        if (!file_exists($path)) {
            if( !\file_exists($public_path . '/images/') ){
                mkdir($public_path . '/images/');
            }

            mkdir($path);
        }

        do { 

           $name = $_name . $counter . '.' . $file->getClientOriginalExtension();

           $counter = (int) $counter;

           $counter++;

        } while(file_exists( $path . $name));

        $isUploaded = $file->move($path, $name);

        return '/images/' . $entity . '/' . $name;
	}
}
