<?php

namespace App\Validator;

trait Role{
    protected $rules = [
        "role_name" => "required|max:255",
        "role_key" => "sometimes|required|unique:roles,role_key",
        "url" => "sometimes|required|unique:roles,url",
    ];
}
