<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\OrderHistory as Validator;

class OrderHistory extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "order_history";
    protected $fillable = ['order_id', 'customer_id', 'user_id', 'order_id', 'weight', 'order_number', 'pickup_address_id', 'status', 'order_status_id',
    'prev_order_status_id', 'cancel_reason_id','aqg_dropoff_loc_id','customer_dropoff_loc_id','disposal_type','required_vehicles','contract_work_permit','required_start_date',
    'estimated_end_date','is_segregation_required','is_collection_required','comments'];
    public $timestamps = true;

    function order() {
      return $this->belongsTo('App\Model\User', 'order_id', 'order_id');
    }

    function maintainOrderHistory($data,$order_id){
      //dd($data['pick_up_address_id']);

      OrderHistory::create(['order_id' => isset($order_id) ? $order_id : null,
      'customer_id' => isset($data['customer_id']) ? $data['customer_id'] : null, 
      'user_id' => $data['user_id'] ,
      'weight' => isset($data['net_weight']) ? $data['net_weight'] : null ,
      'pickup_address_id' =>  isset($data['pickup_address_id']) ? $data['pickup_address_id'] : null,
      'aqg_dropoff_loc_id' => isset($data['aqg_loc_id']) ? $data['aqg_loc_id'] : null ,
      'customer_dropoff_loc_id' => isset($data['customer_dropoff_loc_id']) ? $data['customer_dropoff_loc_id'] : null,
      'disposal_type' => isset($data['disposal_type']) ? $data['disposal_type'] : null,
      'required_vehicles' => isset($data['required_vehicles']) ? $data['required_vehicles'] : null,
      'contract_work_permit' => isset($data['contract_work_permit']) ? $data['contract_work_permit'] : null,
      'required_start_date' => isset($data['start_date']) ? $data['start_date'] : null,
      'estimated_end_date' => isset($data['end_date']) ? $data['end_date'] : null,
      'is_segregation_required' => isset($data['is_segregation_required']) ? $data['is_segregation_required'] : null,
      'is_collection_required' => isset($data['is_collection_required']) ? $data['is_collection_required'] : null,
      'comments' => isset($data['comments']) ? $data['comments'] : null,
      // 'trips' => isset($data['trips']) ? $data['trips'] : null,
      // 'trucks' => isset($data['trucks']) ? $data['trucks'] : null,
      'category_id' => isset($data['category_id']) ? $data['category_id'] : null,
      'order_status_id' => isset($data['order_status_id']) ? $data['order_status_id'] : null,
      'status' => 1]);
    }

//    function add($data){
//       // dd($data);
//         $data['order_id'] = (int) $data['order_id'];
//         $data['order_status_id'] = (int) $data['order_status_id'];
//         $data['user_id'] = (int) $data['user_id'];
//         $data['source_id'] =  $data['source_id'];

//         try{
//             parent::add($data);
//             // dd($data);
//         }
//         catch(\Exception $ex){
//           // dd('1');
//             Error::trigger("order.log.add", [$ex->getMessage()]);
//             // echo '<pre>'.print_r(Error::get("location.add"), true).'</pre>'; exit;

//         }
// }

}
