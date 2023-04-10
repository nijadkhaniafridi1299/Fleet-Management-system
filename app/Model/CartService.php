<?php

namespace App\Model;
use App\Cart as AppCart;
use App\Model\CustomerCart as CustomerCart;


class CartService extends AppCart{
    protected $cart;
    protected $cartId;

    function __construct($customer_id){
        $this->cart = CustomerCart::create([
            "customer_id" => $customer_id,
            "total" => 0.00,
            "status" => 1,
            "discount" => 0.00
        ]);
        $this->cartId = $this->cart->cart_id;    
    }

}
