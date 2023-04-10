<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Status as Validator;

class Status extends Model
{
    use Validator;

    protected $primaryKey = "status_id";
    protected $table = "fm_statuses";
    protected $fillable = [
        'title',
        'code',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("status.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("status.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}


        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("status.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $status_id) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("status.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("status.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}


        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $status_id);
        }
        catch(Exception $ex) {
            Error::trigger("status.change", [$ex->getMessage()]);
        }
    }
}
