<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\OrderLog as Validator;

class OrderLog extends Model
{
    use Validator;

    protected $primaryKey = "order_log_id";
    protected $table = "order_logs";
    protected $fillable = ['order_id', 'order_status_id', 'user_id', 'source_id', 'action'];
    public $timestamps = true;

    function log_status_detail(){
       return $this->belongsTo('App\Model\OrderStatus', 'order_status_id', 'order_status_id');
    }
    function log_source(){
       return $this->belongsTo('App\Model\Source', 'source_id', 'source_id');
    }

    function action() {
      return $this->belongsTo('App\Model\Action', 'action', 'action_id');
    }

    function user() {
      return $this->belongsTo('App\Model\User', 'user_id', 'user_id');
    }

   function add($data){
      // dd($data);
        $data['order_id'] = (int) $data['order_id'];
        $data['order_status_id'] = (int) $data['order_status_id'];
        $data['user_id'] = (int) $data['user_id'];
        $data['source_id'] =  $data['source_id'];

        try{
            parent::add($data);
            // dd($data);
        }
        catch(\Exception $ex){
          // dd('1');
            Error::trigger("order.log.add", [$ex->getMessage()]);
            // echo '<pre>'.print_r(Error::get("location.add"), true).'</pre>'; exit;

        }
}

}
