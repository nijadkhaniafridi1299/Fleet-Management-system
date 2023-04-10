<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Payment as Validator;

class PaymentDetail extends Model
{
    use Validator;

    protected $primaryKey = "payment_detail_id";
    protected $table = "payment_detail";
    protected $fillable = ["payment_id", "payment_detail"];
    protected $attributes = [ "status" => 1];
    public $timestamps = false;

}
