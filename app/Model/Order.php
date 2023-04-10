<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Model\CancelReason as AppCancelReasons;
use App\Validator\Order as Validator;
use App\Model\DeliverySlot as DeliverySlot;
use App\Model\Delivery as Delivery;
use App\Model\DeliveryTrip as DeliveryTrip;
use App\Model\Vehicle as Vehicle ;
use App\Model\User as User ;
use App\Model\OrderServiceREquest ;
use App\Model\CompanyWarehouse ;
use App\Model\CustomerWarehouse ;
use App\Model\Store ;
use DB;
use Auth;

class Order extends Model
{
    use Validator;

    protected $primaryKey = "order_id";
    protected $table = "orders";

    protected $fillable = [
        'customer_id',
        'shipping_address_id',
        'payment_method',
        // 'shipping_method',
        'status',
        'discount',
        'total',
        'vat',
        'site_location',
        'grand_total',
        'status_driver',
        'status_delivered',
        'status_cancelled',
        'erp_id',
        'promocode_id',
        'order_number',
        'order_status_id',
        'rating',
        'source_id',
        'cart_id',
        'cancel_reason',
        'cancel_reason_id',
        'prev_order_status_id',
        // 'delivery_time',
        // 'gift_products',
        'preferred_date',
        'amount_due',
        'amount_paid',
        'balance',
        'company_id',
        // 'delivery_day',
        'aqg_dropoff_loc_id',
        'customer_dropoff_loc_id',
        'net_weight',
        'unit',
        'department_name',
        'dpc',
        'contact_person',
        'phone',
        'log_in_id',
        'disposal_type',
        'required_vehicles',
        // 'material_types',
        'contract_work_permit',
        'required_start_date',
        'estimated_end_date',
        'pickup_address_id',
        'is_segregation_required',
        'is_collection_required',
        'comments',
        'cancelation_details',
        'category_id',
        'created_by',
        'created_at',
        'updated_at',
        'customer_lot_id',
        'ready_for_pickup'
    ];

    protected $attributes = ['status'=> 1, "discount" => 0, 'total' => 0.00]; 
    // 'is_favourite' => 0, 'shipping_address_id'=>0, 
    // "source_id" => 1, 'gift_products'=>'{}'];

    public $timestamps = false;

    protected static $columns = [
        "order_number" => "Order Number",
        "created_at" => "Order Date",
        "payment_method" => "Payment Method",
        "delivery_slot" => "Delivery Slot",
        "total" => "Total w/o VAT",
        "vat" => "VAT",
        "grand_total" => "GrandTotal",
        "location_id" => "City",
        "address" => "Area",
        "order_status_title" => "Order Status",
        "status" => "Status"
    ];

    public static function getTableColumns() {
        return self::$columns;
    }

    function customer(){
        return $this->belongsTo('App\Model\Customer', 'customer_id');
    }

    function lot(){
        return $this->belongsTo('App\Model\CustomerLot', 'customer_lot_id')->select(['customer_lot_id','lot_number']);
    }

    function weight_unit(){
        return $this->belongsTo('App\Model\Unit', 'unit' , 'id');
    }

    function order_material(){
        return $this->hasMany('App\Model\OrderMaterial', 'order_id')->select(['material_id','order_id', 'unit', 'weight','skip_id']);
    }

    function cancel_id(){
        return $this->belongsTo('App\Model\CancelReason', 'cancel_reason_id','cancel_reason_id');
    }

    function items(){
        return $this->hasMany('App\Model\OrderItem', 'order_id');
    }

    function source(){
        return $this->belongsTo("App\Model\Source", "source_id");
    }

    function address(){
        return $this->belongsTo('App\Model\Address', 'pickup_address_id', 'address_id');
    }

    function aqg(){
        return $this->belongsTo('App\Model\Store', 'aqg_dropoff_loc_id', 'store_id');
    }

    function customer_warehouse(){
        return $this->belongsTo('App\Model\Address', 'customer_dropoff_loc_id', 'address_id');
    }

    function pickup(){
        return $this->belongsTo('App\Model\Address', 'pickup_address_id', 'address_id');
    }

    function site_location(){
        return $this->belongsTo('App\Model\Address', 'site_location', 'address_id');
    }

    function customer_dropoff() {
        return $this->belongsTo('App\Model\Address', 'customer_dropoff_loc_id', 'address_id');
    }

    function addressWithTrashed(){
        return $this->belongsTo('App\Model\Address', 'shipping_address_id', 'address_id')->withTrashed();
    }

    function shipping_address(){
        return $this->belongsTo('App\Model\Address', 'shipping_address_id', 'address_id');
    }

    function order_status(){
       return $this->belongsTo('App\Model\OrderStatus', 'order_status_id');
    }

    function cancelReasons(){
        return $this->belongsTo('App\Model\CancelReason', 'cancel_reason_id');
    }
    function createdBy(){
        return $this->belongsTo('App\Model\User', 'created_by','user_id');
    }
    function orderServiceRequests(){
        return $this->hasMany('App\Model\OrderServiceRequest', 'order_id');
    }
    function allocatedItems(){
        return $this->hasMany('App\Model\AssetTransaction', 'order_id');
    }

    function category(){
        return $this->belongsTo('App\Model\Category', 'category_id');
    }
    
    function collection_type(){
        return $this->belongsTo('App\Model\CollectionType', 'collection_type_id');
    }
    function payment(){
        return $this->belongsTo('App\Model\Payment','payment_id', 'transaction_key');
    }

    function recurring(){
		return $this->belongsTo('App\Model\RecurringOrder', 'order_number','order_id');
	}
    function promocode(){
		return $this->belongsTo('App\Model\Promocode', 'promocode_id', 'promocode_id');
	}

    function delivery() {
        return $this->belongsTo('App\Model\Delivery', 'order_id', 'order_id');
    }

    function delivery_trips() {
        return $this->hasMany('App\Model\DeliveryTrip', 'order_id', 'order_id');
    }
    
    function active_delivery_trips() {
        return $this->hasMany('App\Model\DeliveryTrip', 'order_id', 'order_id')->where('trip_status_id',2);
    }

    function luggers(){
        $luggers = $this->getLuggers();
        return $this->hasMany('App\Model\OrderServiceRequest', 'order_id')
                    ->whereIn('service_category_id',$luggers);                                      
    }
    
    function iot(){
        $iot = $this->getIOT();
        return $this->hasMany('App\Model\OrderServiceRequest', 'order_id')
                    ->whereIn('service_category_id',$iot);                    
    }

    public function getLuggers()
    {
        $luggers = ServiceCategory::where('key', 'like', '%' . "LUGGER" . '%')->pluck('service_category_id');
        return $luggers;
    }

    public function getIOT()
    {
        $iot = ServiceCategory::where('key', 'like', '%' . "IOT" . '%')->pluck('service_category_id');
        return $iot;
    }

    public function pickupMaterial(){
        return $this->hasManyThrough('App\Model\PickupMaterial' , 'App\Model\DeliveryTrip', 'order_id','trip_id','order_id','delivery_trip_id');
    }

    public function dropoffMaterial(){
        return $this->hasManyThrough('App\Model\DropoffMaterial' , 'App\Model\DeliveryTrip', 'order_id','trip_id','order_id','delivery_trip_id');
    }

    
    
    function add($data) {

        $data["vehicle_code"] = parent::generateCode("vehicle_code", "VEH");

        if (!isset($data['speed'])) {
            $data['speed'] = [];
        }

        $data['speed'] = array_filter($data['speed']);
        $data['speed'] = json_encode($data['speed'], JSON_UNESCAPED_UNICODE);
        
        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("vehicle.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $vehicle_id){

        if (!isset($data['speed'])) {
            $data['speed'] = [];
        }
        
        $data['speed'] = array_filter($data['speed']);
        $data['speed'] = json_encode($data['speed'], JSON_UNESCAPED_UNICODE);

        try{
            return parent::change($data, $vehicle_id);
        }
        catch(Exception $ex){
            Error::trigger("vehicle.change", [$ex->getMessage()]) ;
        }
    }
    function updateDeliveryOrder($data){
        $order_id = $data['order_id'];
        $slot_id = $data['delivery_slot_id'];
        $date = $data['date'];
        $time = $data['deliverytime'];

        $slot = DeliverySlot::where('id',$slot_id);
        if(!is_object($slot)){
            return response()->json([
                "code" => 404,
                "data" => [
                    "order_id" => $order_id,
                    "delivery_slot_id" => $slot_id,
                ],
                "message" => __("slot Not found")
            ]);
        }
      
        $acceptablearray=['1,2,3,5,7,8,9,10,11,12,13,14'];
  
        $statusOrder = \DB::table('orders')->where("order_id", $order_id)
        ->where('order_status_id', '!=', 4)
        ->where('order_status_id', '!=', 6)
        ->get()->toArray();
      
       
        
        if(!count($statusOrder)){
            return response()->json([
                "code" => 404,
                "data" => [
                    "order_id" => $order_id,
                    "delivery_slot_id" => $slot_id,
                ],
                "message" => __("order Not found")
            ]);
        }
       
       $flight = Delivery::where('order_id',$order_id);
       
                        if($flight->get()->isEmpty()){

                                return response()->json([
                                "code" => 404,
                                "data" => [
                                    "order_id" => $order_id,
                                    "delivery_slot_id" => $slot_id,
                                ],
                                "message" => __("Delivery Order Not found")
                                ]);
                        }else{
                    
                            $isUpdated = \DB::table('orders')->where("order_id", $order_id)->whereNull('deleted_at')
                         ->update(["delivery_slot_id" => $slot_id, "preferred_date" => $date]);
                         if($isUpdated){
                             return response()->json([
                                 "code" => 200,
                                 "data" => [
                                     "order_id" => $order_id,
                                     "delivery_slot_id" => $slot_id,
                                 ],
                                 "message" => __("Slot updated successfully")
                             ]);
                            }
                        }
        
                        
                      
                     

        return response()->json([
            "code" => 404,
            "data" => [
                "order_id" => $order_id,
                "delivery_slot_id" => $slot_id,
            ],
            "message" => __("Order Not found")
        ]);
    }


    
    
    static function cancelOrders($data){
        
        $order_id = $data['order_id'];
        $cancel_reason = $data['cancel_reason_id'];

    $checkstatus=Order::
    where('order_id',$order_id)->pluck('order_status_id');

    if($checkstatus->isEmpty()){
        return response()->json([
            "code" => 404,
            "data" => [
                "order_id" => $order_id,
                "status_id" => $cancel_reason,
            ],
            "message" => __("Order Not found")
        ]);
    }
    # Check if order has already been delivered
    if($checkstatus[0] == 4)
    {
        return response()->json([
            "code" => 404,
            "data" => [
                "order_id" => $order_id,
                "status_id" => $cancel_reason,
            ],
            "message" => __("Order Is Already Delivered")
        ]);

    }

    # Cancel_reason_id doesn't exist
        $status = CancelReason::find($cancel_reason);
     
        if(!is_object($status)){
            return response()->json([
                "code" => 404,
                "data" => [
                    "order_id" => $order_id,
                    "status_id" => $cancel_reason,
                ],
                "message" => __("Status Not found")
            ]);
        }

    # Cancelation Explanation
    if(isset($data['explanation']) && $data['explanation'] != null){
        Order::where('order_id', $order_id)->update(['cancelation_details' => $data['explanation']]);
    }
    
        // $trip = Delivery::where('order_id',$order_id)->get();
        
        // $tripid=Delivery::where('order_id', $order_id)
        // ->pluck('delivery_trip_id');
        // $trip_id=$tripid[0];
   
        // $vehicle=DeliveryTrip::where('delivery_trip_id', $tripid)
        // ->pluck('vehicle_id');
       
        // $vehicle_id=$vehicle[0];
   
        
                              
        // $getuserid =  Vehicle::where('vehicle_id',$vehicle_id)->pluck('driver_id');
                       
        // $notification_id =  User::where('user_id', $getuserid[0])->pluck('fcm_token_for_driver_app');
        
        // $notification_id=$notification_id[0];
        
    
               $title = "Order_Cancelled";
        
        
        //  $message = "Order Has Been Cancelled Against Trip ID #" .$trip_id;
         $message = "Order Has Been Canceled Against Trip ID #" ;
        //  $id = $getuserid[0];
        //            $type = "Cancel_Order";
        //            $res = send_notification_FCM($notification_id, $title, $message, $id,$type);
                              

        // if(!$trip->isEmpty()){
         
        //     $tripId = $trip[0]['delivery_trip_id'];
        //     $count = Delivery::where("delivery_trip_id", $tripId)->count();
        //     if($count < 2 ){
        //          $dT = DeliveryTrip::where('delivery_trip_id',$tripId);
        //          $dT->delete();
        //     }
            
        // }
        $statusOrder = \DB::table('orders')->where("order_id", $order_id)->where('order_status_id', '4')->get()->toArray();
        if(count($statusOrder)){
            return response()->json([
                "code" => 404,
                "data" => [
                    "order_id" => $order_id,
                    "status_id" => $cancel_reason,
                ],
                "message" => __("order Not found")
            ]);
        }
        
        $order_status = \DB::table('order_statuses')->where("order_status_id", 6)->where('status', '1')->get();
    
        if(!count($order_status)){

            return response()->json([
            "code" => 404,
            "data" => [
                "order_id" => $order_id,
                "status_id" => $cancel_reason,
            ],
            "message" => __("Order Status Not Found")
        ]);
        }

        $order_status_check = \DB::table('orders')->where("order_status_id", 6)->where('order_id',$order_id)->where('status', '1')->get();
         
        if(count($order_status_check)){

            return response()->json([
            "code" => 404,
            "data" => [
                "order_id" => $order_id,
                "status_id" => $cancel_reason,
            ],
            "message" => __("Order Already Canceled")
        ]);
        }

        $isUpdated = \DB::table('orders')->where("order_id", $order_id)
                         ->update(["order_status_id" => $order_status[0]->order_status_id,"cancel_reason_id" => $cancel_reason,'status_cancelled' =>date('Y-m-d h:i:s')]);
                     if($isUpdated){
                        $flight = DeliveryTrip::where('order_id',$order_id);
                        if(!$flight){
                            return response()->json([
                                "code" => 404,
                                "data" => [
                                    "order_id" => $order_id,
                                    "status_id" => $cancel_reason,
                                ],
                                "message" => __("Order Not found")
                            ]);
                        }
   
                        $deliveryupdated = \DB::table('delivery_trips')->where("order_id", $order_id)
                        ->update(["deleted_at" => date('Y-m-d H:i:s')]);
                        $orderstatusid = DB::table('orders')->where('order_id', $order_id)
                        ->get()->toArray();
                      
                      
                        
                        $user=Auth::user();
                        $orderlogsdata[] = [
                                        
                            'order_id' => $order_id,
                            'order_status_id' => $orderstatusid[0]->order_status_id,
                            'source_id' => 12,
                            'user_id' => $user->user_id,
                            'created_at' =>  date('Y-m-d H:i:s'),
                            'updated_at' =>  date('Y-m-d H:i:s')
                
                           
                    ];
                 
                    OrderLogs::insert($orderlogsdata);
                    


                        $trip = $flight->delete();


                    
                         return response()->json([
                             "code" => 200,
                             "data" => [
                                 "order_id" => $order_id,
                                 "status_id" => $cancel_reason,
                             ],
                             "message" => __("Status updated successfully")
                         ]);
                     }

        return response()->json([
            "code" => 404,
            "data" => [
                "order_id" => $order_id,
                "status_id" => $cancel_reason,
            ],
            "message" => __("Order Not found")
        ]);
    }

    static function createOrderNumber($customer_id){
		$counter = 1;

		$order_number = $customer_id ; //str_pad($customer_id, 3, "0", STR_PAD_LEFT);
		//$order_number .= time();
		$order = static::where("customer_id", $customer_id)->count();
		$counter += $order;

		return $order_number . str_pad($counter, 3, '0', STR_PAD_LEFT);
	}

    function payment_method_info(){
		return $this->belongsTo('App\Model\Option', 'payment_method', 'option_key');
	}

    public function getCancelOrderReasons()
    {
      return AppCancelReasons::where('status',1)
      ->whereNotNull('reason_code')
    //   ->where('mobile_visible',1)
      ->get()->toArray();
    }

    // public function getCreatedAtAttribute($timestamp) {
    //     return \Carbon\Carbon::parse($timestamp)->format('m-d-y h:i:a');
    // }
    // public function getUpdatedAtAttribute($timestamp) {
    //     return \Carbon\Carbon::parse($timestamp)->format('m-d-y h:i:a');
    // }
}
