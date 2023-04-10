<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Parameter as Validator;

class Parameter extends Model
{
    use Validator;

    protected $primaryKey = "parameter_id";
    protected $table = "fm_parameters";
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
			Error::trigger("parameter.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("parameter.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("parameter.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $parameter_id) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("parameter.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("parameter.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try{
            return parent::change($data, $parameter_id);
        }
        catch(Exception $ex) {
            Error::trigger("parameter.change", [$ex->getMessage()]);
        }
    }
}
