<?php

namespace App\Validator;

trait VehicleCategory{
    protected $rules = [
        "vehicle_category.ar" => "required_without:vehicle_category.en"
    ];

    protected $messages = [
        "vehicle_category.ar.required_without" => "Please specify vehicle category."

    ];
}
