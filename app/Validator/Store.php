<?php


namespace App\Validator;

trait Store{
    protected $rules = [
        "store_name.ar" => "sometimes|required_without:store_name.en",
        "store_manager_id" => "nullable|exists:users,user_id",
        "locations.*" => "nullable|exists:locations,location_id",
        "store_address_id" => "nullable|exists:addresses,address_id",
        "parent_id" => "nullable|exists:stores,store_id",
        "store_type_id" => "nullable|exists:store_types,store_type_id",
        "stock.*" => "nullable|numeric",
        "capacity.*" => "nullable|numeric",
        "min_stock_limit.*" => "nullable|numeric",
        "refill_stock_limit.*" => "nullable|numeric",
    ];
 
    protected $messages= [
        "store_name.ar.required_without" => "Please specify warehouse name."
    ];
}



