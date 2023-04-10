<?php

namespace App\Validator;

trait Route{
    protected $rules = [
        "route_code" => "sometimes|required|alpha_num|unique:routes",
        "store_id" => "nullable|exists:stores,store_id",
        "opening_odometer" => "nullable|numeric",
        "closing_odometer" => "nullable|numeric",
        "areas" => "required",
        "areas.*" => "required|exists:locations,location_id",
        "vehicle_id" => "nullable|exists:vehicles,vehicle_id",
        "salesman_id" => "nullable|exists:users,user_id",
        "helper_id" => "nullable|exists:users,user_id",
        "presales_id" => "nullable|exists:users,user_id"
    ];
}
