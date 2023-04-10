<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Event as Validator;

class Event extends Model
{
    use Validator;

    protected $primaryKey = "event_id";
    protected $table = "fm_events";
    public $timestamps = false;
    protected $fillable = [
        'title',
        'event_type_id',
        'system_message',
        'play_sound_on_event',
        'notify_interval',
        'violation_penalty',
        'send_to_emails',
        'send_sms_to_numbers',
        'send_command',
        'command',
        'callback_url',
        'command_type',
        'gateway',
        'unit_status',
        'users',
        'user_groups'
    ];

    protected $attributes = ['status' => 1];

    function eventType() {
        return $this->belongsTo('App\Model\EventType', 'event_type_id', 'event_type_id');
    }

    function vehicles() {
        return $this->hasMany('\App\Model\VehiclesInEvent', 'event_id', 'event_id');
    }

    function vehicleGroups() {
        return $this->hasMany('\App\Model\VehicleGroupsInEvent', 'event_id', 'event_id');
    }

    function activeOnDays() {
        return $this->hasMany('App\Model\EventActiveOnDay', 'event_id', 'event_id');
    }

    function add($data) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("event.add", ["title" => ["en" =>"Please Enter Name in English. Special Characters are not allowed."]]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("event.add", ["title" => ["ar" => "Please Enter Name in Arabic. Special Characters are not allowed."]]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        if (isset($data['users'])) {
            if (count($data['users']) > 0) {
                $data['users'] = array_filter($data['users']);
                $data['users'] = json_encode($data['users'], JSON_UNESCAPED_UNICODE);
            } else {
                unset($data['users']);
            }
        }
        
        if (isset($data['user_groups'])) {
            if (count($data['user_groups'])) {
                $data['user_groups'] = array_filter($data['user_groups']);
                $data['user_groups'] = json_encode($data['user_groups'], JSON_UNESCAPED_UNICODE);
            } else {
                unset($data['user_groups']);
            }
        }
    
        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("event.add", [$ex->getMessage()]);
        }
    }

    function change($data, $event_id) {

        $data['title']['en'] = cleanNameString($data['title']['en']);

		if (!isset($data['title']['en']) || $data['title']['en'] == '') {
			Error::trigger("event.change", ["title" => ["en" =>"Please Enter Name in English. Special Characters are not allowed."]]);
			return false;
		}

        $data['title']['ar'] = cleanNameString($data['title']['ar']);

		if (!isset($data['title']['ar']) || $data['title']['ar'] == '') {
			Error::trigger("event.change", ["title" => ["ar" => "Please Enter Name in Arabic. Special Characters are not allowed."]]);
			return false;
		}

        $data['title'] = array_filter($data['title']);
        $data['title'] = json_encode($data['title'], JSON_UNESCAPED_UNICODE);

        if (isset($data['users'])) {
            if (count($data['users']) > 0) {
                $data['users'] = array_filter($data['users']);
                $data['users'] = json_encode($data['users'], JSON_UNESCAPED_UNICODE);
            } else {
                unset($data['users']);
            }
        }
        
        if (isset($data['user_groups'])) {
            if (count($data['user_groups'])) {
                $data['user_groups'] = array_filter($data['user_groups']);
                $data['user_groups'] = json_encode($data['user_groups'], JSON_UNESCAPED_UNICODE);
            } else {
                unset($data['user_groups']);
            }
        }
    

        try {
            return parent::change($data, $event_id);
        }
        catch(\Exception $ex) {
            Error::trigger("event.change", [$ex->getMessage()]);
        }
    }
}
