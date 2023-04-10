<?php

namespace App\Validator;

trait ServiceType{
    protected $rules = [
        "title" => "required",
        "service_id" => "required|exists:fm_services,service_id"
    ];
}
