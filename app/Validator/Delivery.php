<?php

namespace App\Validator;

trait Delivery{
    protected $rules = [

        "delivery_trip_id" =>"required",
        "order_id" =>"required",

    ];
}
