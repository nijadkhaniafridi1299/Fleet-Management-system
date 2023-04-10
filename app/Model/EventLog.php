<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\EventLog as Validator;

class EventLog extends Model
{
    use Validator;

    protected $primaryKey = "event_log_id";
    protected $table = "events_log";
    public $timestamps = false;
    protected $fillable = [
        'event_id',
        'vehicle_id',
        'message',
        'email_sent',
        'sms_sent',
        'push_notification_sent'
    ];


    function event() {
        return $this->belongsTo('App\Model\Event', 'event_id', 'event_id');
    }

    function vehicle() {
        return $this->belongsTo('App\Model\Vehicle', 'vehicle_id', 'vehicle_id');
    }
}
