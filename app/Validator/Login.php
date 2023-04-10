<?php

namespace App\Validator;

trait Login{
    protected $rules = [

        "email" =>"required|email",
        "password" =>"required|email",


    ];
}
