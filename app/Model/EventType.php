<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\EventType as Validator;

class EventType extends Model
{
    use Validator;

    protected $primaryKey = "event_type_id";
    protected $table = "fm_event_types";
    public $timestamps = false;
    protected $fillable = [
        'title'
    ];

    protected $attributes = ['status' => 1, 'created_by'=>0];

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("eventtype.add", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("eventtype.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("eventtype.add", [$ex->getMessage()]);
        }
    }

    function change($data, $event_id) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("eventtype.change", ["Please Enter Name in English. Special Characters are not allowed."]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("eventtype.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        try {
            return parent::change($data, $event_id);
        }
        catch(\Exception $ex) {
            Error::trigger("eventtype.change", [$ex->getMessage()]);
        }
    }
}
