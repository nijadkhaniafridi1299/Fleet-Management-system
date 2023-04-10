<?php

namespace App\Validator;

trait Event{
    protected $rules = [
        "title" => "required",
        "event_type_id" => "required|exists:fm_event_types,event_type_id"
    ];
}
