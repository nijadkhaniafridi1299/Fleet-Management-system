<?php

namespace App\Validator;

trait DeliverySlot{
    protected $rules = [
        "delivery_slot_title.ar" => "required_without:delivery_slot_title.en"
    ];
    
    protected $messages =[
        "delivery_slot_title.ar.required_without" => "Please specify delivery slot." ,
    ];

}
