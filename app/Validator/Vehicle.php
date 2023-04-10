<?php

namespace App\Validator;

trait Vehicle{
    protected $rules = [
        "vehicle_plate_number" => "sometimes|required|unique:vehicles",
        "vehicle_code" => "sometimes|required|unique:vehicles",
        "status_id" =>"nullable|exists:fm_statuses,status_id",
        "store_id" =>"required",
    ];
}
