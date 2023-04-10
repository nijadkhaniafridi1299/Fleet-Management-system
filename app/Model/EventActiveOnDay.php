<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\EventActiveOnDay as Validator;

class EventActiveOnDay extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "fm_event_active_on_days";
    protected $fillable = ['event_id', 'day_id'];

    /**
     * Ayesha 20-10-2021
     * Active Event(eventId) on multiple days(data => list of day ids)
     */
    function activateDaystForEvent($data, $eventId) {

        $activeDays = EventActiveOnDay::where('event_id', $eventId)->pluck('day_id')->toArray();
       
        //$days = array_column($data, 'day_id');
        
        $days_to_remove = array_diff($activeDays, $data);
        foreach($data as $day_id) {
            //first check whether this day exists or not.
            $day_exists = \App\Model\Day::find($day_id);
           
            if (is_object($day_exists)) {
                $day = [];
                if (isset($activeDays) && count($activeDays) > 0) {
                    if (!in_array($day_id, $activeDays)) {
                        
                        $day['event_id'] = $eventId;
                        $day['day_id'] = $day_id;
                        $day = $this->add($day);
                    }
                } else {
                    $day['event_id'] = $eventId;
                    $day['day_id'] = $day_id;
                    $day = $this->add($day);
                }
            }
        }

        EventActiveOnDay::where('event_id', $eventId)->whereIn('day_id', $days_to_remove)->forceDelete();
    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("eventactiveonday.add", [$ex->getMessage()]);
        }
    }

    function change($data, $id) {

        try {
            return parent::change($data, $id);
        }
        catch(Exception $ex) {
            Error::trigger("eventactiveonday.change", [$ex->getMessage()]);
        }
    }
}
