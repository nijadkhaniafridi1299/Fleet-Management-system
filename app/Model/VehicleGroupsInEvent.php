<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\VehicleGroupsInEvent as Validator;

class VehicleGroupsInEvent extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "fm_vehicle_groups_in_events";
    protected $fillable = ['event_id', 'vehicle_group_id', 'status'];
    protected $attributes = ['status'=>1];

    /**
     * Ayesha 20-10-2021
     * Adds vehicle groups(data => list of group ids) which should be attached in event with eventId
     */
    function addVehicleGroupsInEvent($data, $eventId) {

        $alreadyInEvent = VehicleGroupsInEvent::where('event_id', $eventId)->pluck('vehicle_group_id')->toArray();
        
        $groups_to_remove = array_diff($alreadyInEvent, $data);

        //print_r($alreadyInGroups); exit;

        foreach($data as $group) {
            //first check whether this vehicle group exists or not.
            $group_exists = \App\Model\VehicleGroup::find($group);
            if (is_object($group_exists)) {
                $vehicle_group_in_event = [];
                if (isset($alreadyInEvent) && count($alreadyInEvent) > 0) {
                    if (!in_array($group, $alreadyInEvent)) {
                        
                        $vehicle_group_in_event['event_id'] = $eventId;
                        $vehicle_group_in_event['vehicle_group_id'] = $group;

                        $vehicle_group_in_event = $this->add($vehicle_group_in_event);
                    }
                } else {
                    $vehicle_group_in_event['event_id'] = $eventId;
                    $vehicle_group_in_event['vehicle_group_id'] = $group;
                    
                    $vehicle_group_in_event = $this->add($vehicle_group_in_event);
                }
            }
        }

        VehicleGroupsInEvent::where('event_id', $eventId)->whereIn('vehicle_group_id', $groups_to_remove)->forceDelete();
    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("vehiclegroupsinevent.add", [$ex->getMessage()]);
        }
    }

    function change($data, $id) {

        try {
            return parent::change($data, $id);
        }
        catch(Exception $ex) {
            Error::trigger("vehiclegroupsinevent.change", [$ex->getMessage()]);
        }
    }
}
