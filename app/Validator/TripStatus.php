<?php

namespace App\Validator;

trait TripStatus{
    protected $rules = [

        "trip_status_title" =>"required",
        "status" =>"required",
        "key" =>"required",

    ];
}
