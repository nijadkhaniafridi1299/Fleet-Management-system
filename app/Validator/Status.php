<?php

namespace App\Validator;

trait Status{
    protected $rules = [
        "title" => "required",
        "code" => "required"
    ];
}
