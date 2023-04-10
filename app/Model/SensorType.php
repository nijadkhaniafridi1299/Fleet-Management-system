<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\SensorType as Validator;

class SensorType extends Model
{
    use Validator;

    protected $primaryKey = "sensor_type_id";
    protected $table = "fm_sensor_types";
    protected $fillable = [
        'title',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['created_by'=>0];

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("sensortype.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("sensortype.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("sensortype.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $sensor_type_id){

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("sensortype.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("sensortype.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try{
            return parent::change($data, $sensor_type_id);
        }
        catch(Exception $ex){
            Error::trigger("sensortype.change", [$ex->getMessage()]);
        }
    }
}
