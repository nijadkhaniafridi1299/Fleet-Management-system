<?php

namespace App\Validator;

trait User{

    protected $rules = [
        "email" =>"sometimes|required|email|unique:users",
        "first_name" => "required",
        "last_name" => "required",
        "password" => "sometimes|required"
    ];

}