<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Payment as Validator;
use DB;

class Payment extends Model{

    use Validator;

    protected $primaryKey = "payment_id";
    protected $table = "payments";
    protected $fillable = ["payment_method", "cart_id", "order_id", "payment_method", 'transaction_key','option_key', "amount", "status", "message", "payment_date"];
    public $timestamps = false;

    



    public static function getTableColumns() {
        return self::$columns;
    }




}
