<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\OrderLogs as Validator;

use DB;

class OrderLogs extends Model
{
    use Validator;

    protected $primaryKey = "order_log_id";
    protected $table = "order_logs";
    protected $fillable = ['order_id', 'order_status_id', 'user_id', 'source_id'];
    public $timestamps = true;

    function log_status_detail(){
       return $this->belongsTo('App\Model\OrderStatus', 'order_status_id', 'order_status_id');
    }
    function log_source(){
       return $this->belongsTo('App\Model\Source', 'source_id', 'source_id');
    }

    function order(){
        return $this->belongsTo('App\Model\Order', 'order_id', 'order_id');
    }

    function order_status(){
        return $this->belongsTo('App\Model\OrderStatus', 'order_status_id');
    }

   

}
