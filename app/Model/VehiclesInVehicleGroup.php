<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\VehiclesInVehicleGroup as Validator;

class VehiclesInVehicleGroup extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "vehicles_in_vehicle_groups";
    protected $fillable = ['vehicle_id', 'vehicle_group_id', 'created_by'];
    protected $attributes = ['created_by'=>0];

    function vehicle() {
        return $this->belongsTo('\App\Model\Vehicle', 'vehicle_id', 'vehicle_id');
    }

    function vehicle_group() {
        return $this->belongsTo('\App\Model\VehicleGroup', 'vehicle_group_id', 'vehicle_group_id');
    }

    /**
     * Ayesha 20-10-2021
     * Adds a vehicle(with vehicleId) in groups(data => list of group ids in which vehicle is supposed to be added)
     */
    function addVehicleInGroups($data, $vehicleId) {

        $alreadyInGroups = VehiclesInVehicleGroup::where('vehicle_id', $vehicleId)->pluck('vehicle_group_id')->toArray();
        
        $groups_to_remove = array_diff($alreadyInGroups, $data);

        //print_r($alreadyInGroups); exit;

        foreach($data as $group) {
            //first check whether this vehicle group exists or not.
            $group_exists = \App\Model\VehicleGroup::find($group);
            if (is_object($group_exists)) {
                $vehicle_in_group = [];
                if (isset($alreadyInGroups) && count($alreadyInGroups) > 0) {
                    if (!in_array($group, $alreadyInGroups)) {
                        
                        $vehicle_in_group['vehicle_id'] = $vehicleId;
                        $vehicle_in_group['vehicle_group_id'] = $group;

                        $vehicle_in_group = $this->add($vehicle_in_group);
                    }
                } else {
                    $vehicle_in_group['vehicle_id'] = $vehicleId;
                    $vehicle_in_group['vehicle_group_id'] = $group;
                    
                    $vehicle_in_group = $this->add($vehicle_in_group);
                }
            }
        }

        VehiclesInVehicleGroup::where('vehicle_id', $vehicleId)->whereIn('vehicle_group_id', $groups_to_remove)->forceDelete();

    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("vehiclesinvehiclegroup.add", [$ex->getMessage()]);
        }
    }

    function change($data, $id) {

        try {
            return parent::change($data, $id);
        }
        catch(Exception $ex) {
            Error::trigger("vehiclesinvehiclegroup.change", [$ex->getMessage()]);
        }
    }
}
