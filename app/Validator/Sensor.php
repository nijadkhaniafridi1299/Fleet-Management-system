<?php

namespace App\Validator;

trait Sensor{
    protected $rules = [
        "title" => "required",
        "result_type" => "required",
        "sensor_type_id" => "required",
        "parameter_id" => "required"
    ];
}
