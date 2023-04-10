<?php

namespace App\Validator;

trait UsersUnitSetting{
    protected $rules = [
        "user_id" => "required",
        "title" => "required"
    ];
}
