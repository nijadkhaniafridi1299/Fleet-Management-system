<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\OrderServiceRequestHistory as Validator;

class OrderServiceRequestHistory extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "order_service_requests_history";
    protected $fillable = ['order_id', 'user_id', 'service_category_id', 'quantity', 'capacity','start_date','days_count','remarks','is_client_approval_required','is_govt_approval_required','status','value'];
    public $timestamps = true;

    function maintainServiceRequestHistory($data,$order_id){
        foreach($data['equipment'] as $item){
            if(isset($item['sub_category']) && $item['sub_category']!= null){
                $item['service_category_id'] = $item['sub_category'];
            }
            $category = ServiceCategory::find($item['service_category_id']);
            if($category == null){
                return 'Service category does not exist';
            }
            if(isset($item['qty']) && $item['qty']!= null){
                $item['quantity'] = $item['qty'];
            }
            if(isset($item['date_requested']) && $item['date_requested']!= null){
                $item['start_date'] = $item['date_requested'];
            }
            if(isset($item['days']) && $item['days']!= null){
                $item['days_count'] = $item['days'];
            }
            if(isset($item['client_approval']) ){
                $item['is_client_approval_required'] = $item['client_approval'];
            }
            if(isset($item['gov_approval']) ){
                $item['is_govt_approval_required'] = $item['gov_approval'];
            }
            if(!isset($item['capacity']) ){
                $item['capacity'] = 0;
            }
        $order = OrderServiceRequestHistory::insert(['order_id' => $order_id, 'service_category_id' => $item['service_category_id'],'quantity' => isset($item['quantity']) ? $item['quantity'] : null ,'remarks' => isset($item['remarks']) ? $item['remarks'] : null, 'capacity' => $item['capacity'], 'start_date' => isset($item['start_date']) ? $item['start_date'] : null,'days_count' => isset($item['days_count']) ? $item['days_count'] : null,'is_client_approval_required' => isset($item['is_client_approval_required']) ? $item['is_client_approval_required'] : null, 'is_govt_approval_required' => isset($item['is_govt_approval_required']) ? $item['is_govt_approval_required'] : null,'status' => 1]);
        }
        
            foreach($data['labor'] as $item){
                if(isset($item['label']) && $item['label'] != ""){
                    $id = ServiceCategory::where('title', 'like', '%' . $item['label'] . '%')->value('service_category_id');
                    OrderServiceRequestHistory::create(['order_id' => $order_id,'service_category_id' => $id,'value' => isset($item['value']) ? $item['value'] : null ,'status' => 1]);

                }else{
                    OrderServiceRequestHistory::create(['order_id' => $order_id,'service_category_id' => $item['service_category_id'],'value' => isset($item['value']) ? $item['value'] : null ,'status' => 1]);
                }

        
            }
    }
  

}
