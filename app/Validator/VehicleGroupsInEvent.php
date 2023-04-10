<?php

namespace App\Validator;

trait VehicleGroupsInEvent{
    protected $rules = [
        "event_id" => "required|exists:fm_events,event_id",
        "vehicle_group_id" => "required|exists:vehicle_groups,vehicle_group_id"
    ];
}
