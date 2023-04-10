<?php

namespace App\Validator;

trait Order{
    protected $rules = [
        "customer_id" => "required|exists:customers,customer_id",
        "shipping_address_id" => "required|exists:addresses,address_id",
        "payment_address_id" => "nullable|exists:addresses,address_id",
        "total" => "required|numeric",
        "order_status_id" => "nullable|exists:order_statuses,order_status_id",
        "discount" => "nullable|numeric",
        "vat" => "nullable|numeric",
        "grand_total" => "nullable|numeric",
        // "is_favourite" => "nullable|boolean",
        "cancel_reason_id" => "nullable|exists:cancel_reasons,cancel_reason_id",
        "source_id" => "nullable|exists:sources,source_id",
        "cart_id" => "nullable|numeric",
        // "delivery_time" => "nullable|date",
        "company_id" => "nullable|exists:companies,company_id",
        "delivery_slot_id" => "nullable|exists:delivery_slots,delivery_slot_id",
        "agent_id" => "nullable|exists:users,user_id"
    ];
  
    protected $messages=[];
}
