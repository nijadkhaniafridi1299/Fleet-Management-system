<?php

namespace App\Validator;

trait TripAssignedMaterial{
    protected $rules = [
        "delivery_trip_id" =>"required",
        "material_id" =>"required",
        "weight" =>"required",
    ];
}
