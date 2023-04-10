<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\VehiclesInEvent as Validator;

class VehiclesInEvent extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "fm_vehicles_in_events";
    protected $fillable = ['event_id', 'vehicle_id', 'status'];
    protected $attributes = ['status'=>1];

    /**
     * Ayesha 20-10-2021
     * Adds vehicles(data => list of ids) which should be attached in event with eventId
     */
    function addVehiclesInEvent($data, $eventId) {

        
        $alreadyInEvent = VehiclesInEvent::where('event_id', $eventId)->pluck('vehicle_id')->toArray();
       
        $vehicles_to_remove = array_diff($alreadyInEvent, $data);
        foreach($data as $vehicleId) {
            //first check whether this vehicle exists or not.
            $vehicle = \App\Model\Vehicle::find($vehicleId);
           
            if (is_object($vehicle)) {
                $vehicle_in_event = [];
                if (isset($alreadyInEvent) && count($alreadyInEvent) > 0) {
                    if (!in_array($vehicleId, $alreadyInEvent)) {
                        
                        $vehicle_in_event['event_id'] = $eventId;
                        $vehicle_in_event['vehicle_id'] = $vehicleId;

                        $vehicle_in_event = $this->add($vehicle_in_event);
                    }
                } else {
                    $vehicle_in_event['event_id'] = $eventId;
                    $vehicle_in_event['vehicle_id'] = $vehicleId;
                    
                    $vehicle_in_event = $this->add($vehicle_in_event);
                }
            }
        }

        VehiclesInEvent::where('event_id', $eventId)->whereIn('vehicle_id', $vehicles_to_remove)->forceDelete();
    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("vehiclesinevent.add", [$ex->getMessage()]);
        }
    }

    function change($data, $id) {

        try {
            return parent::change($data, $id);
        }
        catch(Exception $ex) {
            Error::trigger("vehiclesinevent.change", [$ex->getMessage()]);
        }
    }
}
