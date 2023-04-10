<?php

namespace App\Validator;

trait CartItem{

    protected $rules = [
        "cart_id" =>"exists:carts",
        "product_id" =>"exists:products",
    ];

}


