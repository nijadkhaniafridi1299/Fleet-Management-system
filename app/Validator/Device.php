<?php

namespace App\Validator;

trait Device{
    protected $rules = [
        "device_password" => "sometimes|required",
        "device_protocol_id" => "sometimes|required",
        "imei" => "sometimes|required|unique:fm_devices",
        "sim_card_number" => "unique:fm_devices"
    ];
}
