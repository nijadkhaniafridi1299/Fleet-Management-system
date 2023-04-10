<?php

namespace App\Validator;

trait DriverBehavior{
    protected $rules = [
        "title" => "required",
        "vehicle_id" => "required|exists:vehicles,vehicle_id"
    ];
}
