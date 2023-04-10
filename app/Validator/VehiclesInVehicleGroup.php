<?php

namespace App\Validator;

trait VehiclesInVehicleGroup{
    protected $rules = [
        "vehicle_id" => "required",
        "vehicle_group_id" => "required"
    ];
}
