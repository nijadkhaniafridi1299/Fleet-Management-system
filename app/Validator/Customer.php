<?php

namespace App\Validator;

trait Customer{
    protected $rules = [
        //"name.ar" => "required_without:name.en",
        "channel_id" => "nullable|exists:channels,channel_id",
        "source_id" => "nullable|exists:sources,source_id",
        "email" => "nullable|email",
        "mobile" => "nullable|required",
        "agent_id" => "exists:users,user_id",
        "staff_id" => "exists:users,user_id",
        "company_id" => "exists:companies,company_id"
    ];
     protected $messages =[
       // "name.ar.required_without" => "Please specify the name.",
        "email.required" => "Please enter email id.",
        "email.email" => "Enter a valid email id.",
        "source_id.exists" => "Please select customer source.",
        "mobile.regex" => "Enter a valid mobile number.",
        "mobile.min" => "Mobile should be a 10 digits number.",
    ];
}
