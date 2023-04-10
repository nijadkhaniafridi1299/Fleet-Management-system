<?php

namespace App\Validator;

trait OrderMaterial{
    protected $rules = [
        "material_id" =>"required",
        "order_id" =>"required",
        "unit" =>"required",
        "remarks" =>"required",
        "status" =>"required",
    ];
  
    protected $messages=[];
}
