<?php

namespace App\Validator;

trait EventActiveOnDay{
    protected $rules = [
        "event_id" => "required|exists:fm_events,event_id",
        "day_id" => "required|exists:fm_days,day_id"
    ];
}
