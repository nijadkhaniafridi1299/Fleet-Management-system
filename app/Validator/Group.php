<?php

namespace App\Validator;

trait Group{
    protected $rules = [
        "group_name" => "required",
        "group_description" => "required"
    ];
}
