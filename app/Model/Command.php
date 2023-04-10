<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Command as Validator;

class Command extends Model
{
    use Validator;

    protected $primaryKey = "command_id";
    protected $table = "fm_commands";
    protected $fillable = [
        'title',
        'communication_method',
        'command_type_id',
        'vehicle_id',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['created_by'=>0];

    function command_type() {
        return $this->belongsTo('App\Model\CommandType', 'command_type_id', 'command_type_id');
    }

    function vehicle() {
        return $this->belongsTo('App\Model\Vehicle', 'vehicle_id', 'vehicle_id');
    }

    /**
     * add commands for specific vehicle.
     * $data contains list of command ids to be added.
     * if some commands are already added which are no more required for specific vehicle then remove them 
     */
    function addCommandsForVehicle($data, $vehicle_id) {
        $alreadyInVehicle = Command::where('vehicle_id', $vehicle_id)->pluck('command_id')->toArray();

        //get extra commands which are already added, but should be removed now.
        $commands_to_remove = array_diff($alreadyInVehicle, $data);

        try {
            Command::whereIn("command_id", $data)->update(["vehicle_id" => $vehicle_id, "status" => 1]);
            Command::whereIn("command_id", $commands_to_remove)->update(["vehicle_id" => NULL, "status" => 9]);
        } catch(\Exception $ex) {
            Error::trigger("command.add", [$ex->getMessage()]);
        }
    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("command.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $command_id) {

        try {
            return parent::change($data, $command_id);
        }
        catch(Exception $ex) {
            Error::trigger("command.change", [$ex->getMessage()]);
        }
    }
}
