<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\DriverGroup as Validator;

class DriverGroup extends Model
{
    use Validator;

    protected $primaryKey = "driver_group_id";
    protected $table = "fm_driver_groups";
    protected $fillable = ['title', 'status'];
    protected $attributes = ['status' => 1];

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("drivergroup.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("drivergroup.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}


        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("drivergroup.add", [$ex->getMessage()]);
        }
    }

    function change($data, $driver_group_id) {

       
        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("drivergroup.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("drivergroup.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        
        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $driver_group_id);
        }
        catch(Exception $ex) {
            Error::trigger("drivergroup.change", [$ex->getMessage()]);
        }
    }
}
