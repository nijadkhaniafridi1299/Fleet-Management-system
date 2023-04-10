<?php

namespace App\Validator;

trait DriversInDriverGroup{
    protected $rules = [
        "driver_id" => "required|exists:users,user_id",
        "driver_group_id" => "required|exists:fm_driver_groups,driver_group_id"
    ];
}
