<?php


namespace App\Validator;

    trait StoreType{
        protected $rules = [
            "store_type.ar" => "required_without:store_type.en"
        ];
     
        protected $messages= [
            "store_type.ar.required_without" => "Please specify warehouse type."
        ];
}



