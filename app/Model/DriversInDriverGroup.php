<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\DriversInDriverGroup as Validator;

class DriversInDriverGroup extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "fm_drivers_in_driver_groups";
    protected $fillable = ['driver_id', 'driver_group_id'];
    protected $attributes = ['status'=>1];

    // function driver() {
    //     return $this->belongsTo('\App\Model\Driver', 'driver_id', 'driver_id');
    // }

    function driver_group() {
        return $this->belongsTo('\App\Model\DriverGroup', 'driver_group_id', 'driver_group_id');
    }

    /**
     * Ayesha 20-10-2021
     * Adds a driver(with driverId) in groups(data => list of group ids in which driver is supposed to be added)
     */
    function addDriverInGroups($data, $driverId) {

        $alreadyInGroups = DriversInDriverGroup::where('driver_id', $driverId)->pluck('driver_group_id')->toArray();
        
        $groups_to_remove = array_diff($alreadyInGroups, $data);

        foreach($data as $group) {
            //first check whether this driver group exists or not.
            $group_exists = \App\Model\DriverGroup::find($group);

            if (is_object($group_exists)) {
                $driver_in_group = [];
                if (isset($alreadyInGroups) && count($alreadyInGroups) > 0) {
                    if (!in_array($group, $alreadyInGroups)) {
                        
                        $driver_in_group['driver_id'] = $driverId;
                        $driver_in_group['driver_group_id'] = $group;

                        $driver_in_group = $this->add($driver_in_group);
                    }
                } else {
                    $driver_in_group['driver_id'] = $driverId;
                    $driver_in_group['driver_group_id'] = $group;
                    
                    //echo print_r($driver_in_group); exit;
                    $driver_in_group = $this->add($driver_in_group);

                    //echo print_r($driver_in_group); exit;
                }
            }
        }

        DriversInDriverGroup::where('driver_id', $driverId)->whereIn('driver_group_id', $groups_to_remove)->forceDelete();
    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            echo print_r($ex->getMessage()); exit;
            Error::trigger("driversindrivergroup.add", [$ex->getMessage()]);
        }
    }

    function change($data, $id) {

        try {
            return parent::change($data, $id);
        }
        catch(Exception $ex) {
            Error::trigger("driversindrivergroup.change", [$ex->getMessage()]);
        }
    }
}
