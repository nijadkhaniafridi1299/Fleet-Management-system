<?php

namespace App\Validator;

trait DeliveryTrip{
    protected $rules = [

        "trip_code" =>"required",
        "vehicle_id" =>"required",
        "status" =>"required",

    ];
}
