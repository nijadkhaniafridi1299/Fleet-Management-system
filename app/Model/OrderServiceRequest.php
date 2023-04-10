<?php

namespace App\Model;

use App\Model;
use App\Model\ServiceCategory;
use App\Model\Unit;
use App\Validator\OrderServiceRequest as Validator;
use DB;
use Auth;

class OrderServiceRequest extends Model
{
    use Validator;

    protected $primaryKey = "order_service_request_id";
    protected $table = "order_service_requests";

    protected $fillable = [
        'order_id',
        'service_category_id',
        'quantity',
        // 'capacity',
        'start_date',
        'days_count',
        'status',
        'skip_id',
        'is_client_approval_required',
        'is_govt_approval_required',
        'value',
        'material_id',
        'temp_assets',
        'remarks'
    ];
    
    protected $casts = [
        'temp_assets' => 'array'
    ];

    function service_category(){
        return $this->belongsTo('App\Model\ServiceCategory', 'service_category_id','service_category_id');
    }
    
    public function skip(){
        return $this->belongsTo('App\Model\Skip' ,'skip_id','skip_id');
    }
    public function material(){
        return $this->belongsTo('App\Model\Material','material_id','material_id');
    }
    public function order(){
        return $this->belongsTo('App\Model\Order','order_id','order_id');
    }


     # display weigh bridge
     function getModal($order_id){
        $modal = ServiceCategory::model();
        $modal = OrderServiceRequest::whereIn('service_category_id',$modal)->where('order_id',$order_id)->whereStatus(1)->get()->toArray();
        return $modal;
     }

     #To display list of equipments against a specific order
     function listOfequipment($order_id){
        $equipment = ServiceCategory::equipment();
        $equipment_list = OrderServiceRequest::with(['material:material_id,material_code,name','service_category.parent','service_category.items.transactions' => function($query) use ($order_id) {
            $query->where('order_id',$order_id);
        } ])
        // ->orDoesntHave('service_category.items.transactions')
        ->whereIn('service_category_id',$equipment)
        ->where('order_id',$order_id)->whereStatus(1)->get()->toArray();

        return $equipment_list;
    }

    function listOfLabors($order_id){
        $labors = ServiceCategory::labor();
        $labors_list = OrderServiceRequest::with(['service_category.parent','service_category.items.transactions' => function($query) use ($order_id) {
            $query->where('order_id',$order_id);
        }])
        ->whereIn('service_category_id',$labors)
        ->where('order_id',$order_id)->whereStatus(1)->get()->toArray();
        return $labors_list;
    }

    function listOfTools($order_id){
        $tools = ServiceCategory::tools();
        $tools_list = OrderServiceRequest::with(['service_category.parent','service_category.items.transactions' => function($query) use ($order_id) {
            $query->where('order_id',$order_id);
        }])
        ->orDoesntHave('service_category.items.transactions')
        ->whereIn('service_category_id',$tools)->where('order_id',$order_id)->whereStatus(1)->get()->toArray();
        return $tools_list;
    }

    function listOfAssets($order_id){
        $assets = ServiceCategory::assets();
        $assets_list = OrderServiceRequest::with(['material:material_id,material_code,name','service_category.parent','service_category.items.transactions'=> function($query) use ($order_id) {
            $query->where('order_id',$order_id);
        },'skip.current_skip_level:skip_level_id,skip_level,color','skip.asset_inventory:asset_id,title','skip.asset_transaction'])
        ->whereIn('service_category_id',$assets)->where('order_id',$order_id)->whereStatus(1)->get()->toArray();
        return $assets_list;
    }

    function skipOrder($param,$order_id,$start_date = null){

        $skips = [];
        $assets = [];
        $order_material = [];
        foreach($param as $item){
            
            $asset_id = Skip::with('asset_inventory:asset_id,service_category_id')->where('skip_id',$item['skip_id'])->get();
            array_push($assets,$asset_id[0]['asset_id']);

            $material_id = Skip::where('skip_id',$item['skip_id'])->value('material_id');
            if(isset($material_id)){
                array_push($order_material, [
                    'material_id' => $material_id,
                    'skip_id' => $item['skip_id'],
                    'status' => 1 ,
                    'replace' => $item['replace'] ,
                    'order_id' => $order_id
                  ]);
            }
            
            
            array_push($skips, [
                'skip_id' => $item['skip_id'],
                'service_category_id' => $asset_id[0]['asset_inventory']['service_category_id'] ,
                'replace' => $item['replace'] ,
                'status' => 1 ,
                'start_date' => $start_date,
                'order_id' => $order_id
              ]);
        }

        if(count($order_material) > 0){
            OrderMaterial::insert($order_material);
        }
        if(count($skips) > 0){
            OrderServiceRequest::insert($skips);
        }
        if(count($assets) > 0){
            AssetInventory::whereIn('asset_id',$assets)->update(['allocated' => 0]);
        }

    }

    function orderPlacement($param,$cat,$order_id){
        if (OrderServiceRequest::where('order_id',$order_id)->count() > 0) {
            return;
         }
      
        foreach($param as $item){
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
            // if(!isset($item['capacity']) ){
            //     $item['capacity'] = 0;
            // }
            
            $order = OrderServiceRequest::create(['order_id' => $order_id, 'service_category_id' => $item['service_category_id'],
                                                        'quantity' => isset($item['quantity']) ? $item['quantity'] : 0 ,
                                                        'remarks' => isset($item['remarks']) ? $item['remarks'] : null ,
                                                        // 'capacity' => isset($item['capacity']) ? $item['capacity'] : null ,
                                                        'start_date' => isset($item['start_date']) ? $item['start_date'] : null,
                                                        'days_count' => isset($item['days_count']) ? $item['days_count'] : null,
                                                        'is_client_approval_required' => isset($item['is_client_approval_required']) ? $item['is_client_approval_required'] : null,
                                                        'is_govt_approval_required' => isset($item['is_govt_approval_required']) ? $item['is_govt_approval_required'] : null ,
                                                        'material_id' => isset($item['material_id']) ? $item['material_id'] : null ,
                                                        'status' => 1]);
        }
       
                
    }
}
