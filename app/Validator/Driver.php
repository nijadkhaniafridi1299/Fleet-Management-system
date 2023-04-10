<?php

namespace App\Validator;

trait Driver{
    protected $rules = [
        "user_id" => "required|exists:users,user_id"
    ];
}
