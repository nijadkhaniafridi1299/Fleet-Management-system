<?php

namespace App\Validator;

trait Location{
    protected $rules = [
        "parent_id" => "exists:locations,location_id",
        "location_level_id" => "exists:location_levels,location_level_id",
        "location_name.en" => "required",
        "longitude" => "nullable|numeric",
        "latitude" => "nullable|numeric",
        "companies.*" => "nullable|exists:companies,company_id"
    ];
    protected $messages=[
       "parent_id.exists" => "Please select from list. ",
        "location_name.en.required" => "Please specify Location name in English.",
    ];
}
