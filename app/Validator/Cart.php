<?php

namespace App\Validator;

trait Cart{

    protected $rules = [
        "customer_id" =>"exists:customers"
    ];

}


