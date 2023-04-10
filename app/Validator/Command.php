<?php

namespace App\Validator;

trait Command{
    protected $rules = [
        "title" => "required",
        "command_type_id" => "required|exists:fm_command_types,command_type_id"
    ];
}
