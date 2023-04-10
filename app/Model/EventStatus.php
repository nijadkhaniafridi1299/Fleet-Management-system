<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\EventStatus as Validator;

class EventStatus extends Model
{
    use Validator;

    protected $primaryKey = "event_status_id";
    protected $table = "fm_event_statuses";
    protected $fillable = [
        'title'
    ];

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("eventstatus.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("eventstatus.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}


        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("eventstatus.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $event_status_id) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("eventstatus.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("eventstatus.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}


        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $event_status_id);
        }
        catch(Exception $ex) {
            Error::trigger("eventstatus.change", [$ex->getMessage()]);
        }
    }
}
