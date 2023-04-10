<?php

namespace App\Validator;

trait OrderStatus{
    protected $rules = [
        "order_status_title.ar" => "required_without:order_status_title.en"
    ];
 
    protected $messages = [
        "order_status_title.ar.required_without" => "Please specify order status.",
    ];
}
