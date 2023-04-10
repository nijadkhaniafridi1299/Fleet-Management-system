<?php

namespace App\Validator;

trait VehiclesInEvent{
    protected $rules = [
        "event_id" => "required|exists:fm_events,event_id",
        "vehicle_id" => "required|exists:vehicles,vehicle_id"
    ];
}
