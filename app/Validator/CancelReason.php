<?php

namespace App\Validator;

trait CancelReason{
    protected $rules = [
        "reason.ar" => "required_without:reason.en",
        "reason_code" => "nullable|alpha",
        "sort_order" => "nullable|numeric"
    ];
  
    protected $messages = [
        "reason.ar.required_without" => "Please specify cancel reason.",
    ];
}
