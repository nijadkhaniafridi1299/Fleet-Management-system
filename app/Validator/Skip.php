<?php

namespace App\Validator;

trait Skip{
    protected $rules = [
        "skip_password" => "required",
        "imei" => "sometimes|required|unique:skips",
        "sim_card_number" => "unique:skips"
    ];
}
