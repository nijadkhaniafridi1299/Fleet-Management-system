<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use DB;
use App\Message\Error;
use Illuminate\Validation\Rule;
use DateTime;
use Validator;
use App\Model\DeliveryTrip as DeliveryTrip;
class GraphController extends Controller

{

    public function __construct()
    {
        
    }

    public function OrderStatusGraph(Request $request,$store_id)
    {


        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

    
            $confirmed=DB::table('orders')
            ->join('order_statuses', 'order_statuses.order_status_id', '=', 'orders.order_status_id')
            ->whereBetween('orders.created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])   
            ->where('order_statuses.key','CONFIRMED')
            ->count();
            $shipped=DB::table('orders')
            ->join('order_statuses', 'order_statuses.order_status_id', '=', 'orders.order_status_id')
            ->whereBetween('orders.status_driver', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])   
            ->where('order_statuses.key','SHIPPED')
            ->count();
            $delivered=DB::table('orders')
            ->join('order_statuses', 'order_statuses.order_status_id', '=', 'orders.order_status_id')
            ->whereBetween('orders.status_delivered', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])   
            ->where('order_statuses.key','DELIVERED')
            ->count();
            $placed=DB::table('orders')
            ->join('order_statuses', 'order_statuses.order_status_id', '=', 'orders.order_status_id')
            ->whereBetween('orders.created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])   
            ->where('order_statuses.key','PLACED')
            ->count();
            $canceled=DB::table('orders')
            ->join('order_statuses', 'order_statuses.order_status_id', '=', 'orders.order_status_id')
            ->whereBetween('orders.status_cancelled', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])   
            ->where('order_statuses.key','CANCELED')
            ->count();
            
            $assigned=DB::table('orders')
            ->join('order_statuses', 'order_statuses.order_status_id', '=', 'orders.order_status_id')
            ->whereBetween('orders.created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])   
            ->where('order_statuses.key','ASSIGNED')
            ->count();
            $ready=DB::table('orders')
            ->join('order_statuses', 'order_statuses.order_status_id', '=', 'orders.order_status_id')
            ->whereBetween('orders.created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])   
            ->where('order_statuses.key','READY_FOR_PICK_UP')
            ->count();

    
try
{ 
                 
                        
            $response = [
                                    "code" => 200,
                      
                                "data" => [
                                 "CONFIRMED" =>   $confirmed,
                                 "SHIPPED" =>   $shipped,
                                 "DELIVERED" =>   $delivered,
                                 "PLACED" =>   $placed,
                                 "CANCELED" =>   $canceled,
                                 "ASSIGNED" =>   $assigned,
                                 "READY FOR PICKUP" =>   $ready,

                                

                                 
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];


return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
} 




}

public function OrderDeliveredGraph(Request $request,$store_id)
{

    
  

 
        
        $validator = Validator::make([
            
            
            'store_id' => $store_id
        ],[
      
        'store_id' => 'required|int|min:1|exists:stores,store_id'
    ]);

    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
$current = date('Y-m-d H:i:s');
$lastsix = date('Y-m-d H:i:s', strtotime('-6 hour'));
$lasttwelve = date('Y-m-d H:i:s', strtotime('-12 hour'));
$lastthirtysix = date('Y-m-d H:i:s', strtotime('-36 hour'));

        $sixhours=DB::table('orders')
        ->whereBetween('orders.status_delivered', [$lastsix, $current])   
        ->count();
        $twelvehours=DB::table('orders')
        ->whereBetween('orders.status_delivered', [$lasttwelve, $current])   
        ->count();
        $thirtysixhours=DB::table('orders')
        ->whereBetween('orders.status_delivered', [$lastthirtysix, $current])   
        ->count();



try
{ 
             
                    
        $response = [
                                "code" => 200,
                  
                            "data" => [
                             "Last_Six" =>   $sixhours,
                             "Last_Twelve" =>   $twelvehours,
                             "Last_ThirtySix" =>   $thirtysixhours,
                          

                            

                             
                             "error" => 'No Error'
                             ],
                             'message' => 'Successful',
                         ];


return response()->json($response);
}
catch(\Exception $ex)
{
return response()->json([
    "code" => 500,
    "message" => $ex->getMessage()
]);
}    
    
}
public function VehicleStatusGraph(Request $request,$store_id,$date)
{
   
  

   
    $data = ['route_date'=>$date,'store_id' => $store_id];
  

    if($date!= NULL || $date != null){
        
        $validator = Validator::make([
            
            'date' => $date,
             'store_id' => $store_id
        ],[
        'date' => 'nullable|date|min:1',

        'store_id' => 'required|int|min:1|exists:stores,store_id'
    ]);
}else{
    return response()->json([
        'status' => 'error',
        'code' => '300',
        'message' => 'Date Missing!'
    ]);
}
if ($validator->fails()) {
    return responseValidationError('Fields Validation Failed.', $validator->errors());
}

$availablevehicle =  \DB::select("SELECT v.vehicle_id , v.vehicle_code , v.vehicle_plate_number , v.driver_id,v.vehicle_type_id 
,v.vehicle_category_id , v.store_id  
from vehicles v 
JOIN users u on u.user_id = v.driver_id     
where v.vehicle_id NOT IN (SELECT vehicle_id from 
delivery_trips dt                                 
where
(CASE 
                    WHEN dt.deleted_at is NULL THEN (date(dt.trip_date) = '$date' 
                    AND dt.trip_endtime is NULL) 
                   
                    
                    END)
        AND dt.store_id = $store_id
)
AND v.status = 1
AND v.store_id = $store_id");
$assignedvehicle =  \DB::select("SELECT vehicle_id from 
delivery_trips dt                                 
where
(CASE 
                    WHEN dt.deleted_at is NULL THEN (date(dt.trip_date) = '$date' 
                    AND dt.trip_endtime is NULL) 
                   
                    
                    END)
        AND dt.store_id = $store_id");
        $startedvehicle =  \DB::select("SELECT vehicle_id from 
        delivery_trips dt                                 
        where
        (CASE 
                                WHEN dt.deleted_at is NULL THEN (date(dt.trip_date) = '$date' 
                                AND dt.trip_startime is NOT NULL
                                AND dt.trip_endtime is NULL) 
                               
                                
                                END)
                    AND dt.store_id = $store_id");


try
{ 
             
                    
        $response = [
                                "code" => 200,
                  
                            "data" => [
                             "Available Vehicles" =>  count( $availablevehicle),
                             "Assigned Vehicles" =>   count($assignedvehicle),
                             "OnTrip Vehicles" =>   count($startedvehicle),
                          

                            

                             
                             "error" => 'No Error'
                             ],
                             'message' => 'Successful',
                         ];


return response()->json($response);
}
catch(\Exception $ex)
{
return response()->json([
    "code" => 500,
    "message" => $ex->getMessage()
]);
}    
    
}
public function DeliveredOrderPerChannel(Request $request,$store_id)
{
    $dates=json_decode($request->get("data"),true);
  

    if($dates != NULL || $dates != null){
        
        $validator = Validator::make([
            
            'date_from' => $dates['date_from'],
            'date_to' => $dates['date_to'],
            'store_id' => $store_id
        ],[
        'date_from' => 'nullable|date|min:1',
        'date_to' => 'nullable|date|min:1',
        'store_id' => 'required|int|min:1|exists:stores,store_id'
    ]);
}else{
    return response()->json([
        'status' => 'error',
        'code' => '300',
        'message' => 'Date Missing!'
    ]);
}
if ($validator->fails()) {
    return responseValidationError('Fields Validation Failed.', $validator->errors());
}
$datefrom=$dates['date_from'];
$dateto=$dates['date_to'];
$checkstatus=$dates['activeCard'];

if($checkstatus == 'ALL')
{
$orderperchannel= \DB::select("SELECT JSON_UNQUOTE( JSON_EXTRACT(channels.channel_name ,'$.en')) as channel_name , COUNT(orders.order_id) as total_orders,date(orders.created_at) as order_date  from orders
inner join customers
on orders.customer_id = customers.customer_id
inner join channels on customers.channel_id = channels.channel_id
where (date(orders.created_at) BETWEEN ('$datefrom') AND ('$dateto'))
group by customers.channel_id ,order_date,channel_name
order by order_date ASC");
$title='Total';

}
else{
$getid=DB::table('order_statuses')
->where('key',$checkstatus)
->pluck('order_status_id');
$getid=$getid[0];
$orderperchannel= \DB::select("SELECT JSON_UNQUOTE( JSON_EXTRACT(channels.channel_name ,'$.en')) as channel_name , COUNT(orders.order_id) as total_orders,date(orders.created_at) as order_date  from orders
inner join customers
on orders.customer_id = customers.customer_id
inner join channels on customers.channel_id = channels.channel_id
where
order_status_id = $getid and
(date(orders.created_at) BETWEEN ('$datefrom') AND ('$dateto'))
group by customers.channel_id ,order_date,channel_name
order by order_date ASC");
$gettitle=DB::table('order_statuses')
->where('key',$checkstatus)
->pluck('order_status_title');
$title=json_decode($gettitle[0])->en;
}



try
{ 
             
                    
        $response = [
                              "code" =>200,
                            "data" => [
                             "Delivered_Order_Per_Channel" =>   $orderperchannel,
                               "Chart_Title" =>  $title.' Orders Per Channel',

                              
                             "error" => 'No Error'
                             ],
                             'message' => 'Successful',
                         ];

return response()->json($response);
}
catch(\Exception $ex)
{
return response()->json([
    "code" => 500,
    "message" => $ex->getMessage()
]);
}   
    
}
public function TripTypeGraph(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   
        $ttdynamic=DeliveryTrip::where('delivery_trip_type','Dynamic')
        ->whereBetween('trip_date',  [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])->count();
        $ttstatic=DeliveryTrip::where('delivery_trip_type','Static')
        ->whereBetween('trip_date',  [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])->count();
        $ttcustom=DeliveryTrip::where('delivery_trip_type','Custom')
        ->whereBetween('trip_date',  [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])->count();


        try
{ 
                 
                        
            $response = [
                                 "code" => 200,
                      
                                "data" => [
                                 "Dynamic" =>   $ttdynamic,
                                 "Static" =>   $ttstatic,
                                 "Custom" =>   $ttcustom,
                                 
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];


return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
           
    }

    public function TripStatusGraph(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }



        $tsassigned=DeliveryTrip::where('trip_status_id','1')
        ->whereBetween('created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])
        ->count();
        $tsstarted=DeliveryTrip::where('trip_status_id','2')
        ->whereBetween('created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])
        ->count();
        $tspartiallyclosed=DeliveryTrip::where('trip_status_id','3')
        ->whereBetween('created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])
        ->count();
        $tsclosed=DeliveryTrip::where('trip_status_id','4')
        ->whereBetween('created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])
        ->count();
        $tsapproval=DeliveryTrip::where('trip_status_id','5')
        ->whereBetween('created_at', [$dates['date_from']." 00:00:00", $dates['date_to']." 23:59:59"])
        ->count();

      
      
        
      


   try
   { 
                    
                           
               $response = [
                                      "code" => 200,
                                   "data" => [
                                    "Assigned" =>   $tsassigned,
                                    "Started" =>   $tsstarted,
                                    "Partially Closed" =>   $tspartiallyclosed,
                                    "Closed" =>   $tsclosed,
                                    "Waiting For Approval" =>   $tsapproval,
                                    
                                    "error" => 'No Error'
                                    ],
                                    'message' => 'Successful',
                                ];
   
   
   return response()->json($response);
   }
   catch(\Exception $ex)
   {
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
   }   
       
   
      
        
    }
    public function PeakTimeGraph(Request $request,$store_id)
    {
      
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }



        $ptgraph= DB::table('deliveries')
        ->whereBetween('deliveries.delivery_time',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
        ->join('delivery_trips', 'delivery_trips.delivery_trip_id', '=', 'deliveries.delivery_trip_id')
        ->where('delivery_time', '!=', null ) 
        ->get('delivery_time');
       
        
   try
   { 
                    
                           
               $response = [
                "code" => 200,
                         
                                   "data" => [
                                    "Delivery Time" =>   $ptgraph,
                                 
                                    
                                    "error" => 'No Error'
                                    ],
                                    'message' => 'Successful',
                                ];
   
   
   return response()->json($response);
   }
   catch(\Exception $ex)
   {
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
   }   
       

        
    }

    public function PaymentMethodsGraph(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $applepay= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','APPLE_PAY')
   ->count();
   $cashondelivery= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','CASH_ON_DELIVERY')
   ->count();
   $creditcard= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','CREDIT_CARD')
   ->count();
   $sadad= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','SADAD')
   ->count();
   $wallet= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','WALLET')
   ->count();
   $stcpay= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','STC_PAY')
   ->count();
   $mada= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','MADA')
   ->count();
   $customercredit= DB::table('orders')
   ->whereBetween('orders.created_at',[$dates['date_from']." 00:00:00",$dates['date_to']." 23:59:59"])
   ->join('options', 'orders.payment_method', '=', 'options.option_key')
   ->where('orders.payment_method','CUSTOMER_CREDIT')
   ->count();



try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Apple Pay" =>   $applepay,
                                 "Cash On Delivery" =>   $cashondelivery,
                                 "Credit Card" =>   $creditcard,
                                 "Sadad" =>   $sadad,
                                 "Wallet" =>   $wallet,
                                 "STC Pay" =>   $stcpay,
                                 "Mada" =>   $mada,
                                 "Customer Credit" =>   $customercredit,

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }
     
    public function HotAreasGraph(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];

$dynamichagraph =\DB::select('select JSON_EXTRACT(location_name, "$.en") as area_name,count(JSON_EXTRACT(location_name, "$.en")) as area_count from orders
inner join addresses on addresses.address_id = orders.shipping_address_id
inner join deliveries d2 on d2.order_id = orders.order_id
INNER join delivery_trips dt on dt.delivery_trip_id  = d2.delivery_trip_id
inner join locations on locations.location_id  = addresses.location_id
where (date(orders.created_at) between ('."'".$fdate."'".') 
 and ('."'".$tdate."'".')) 
 and delivery_trip_type ="Dynamic"
group by(JSON_EXTRACT(location_name, "$.en")) order by area_count desc limit 10');
$customhagraph =\DB::select('select JSON_EXTRACT(location_name, "$.en") as area_name,count(JSON_EXTRACT(location_name, "$.en")) as area_count from orders
inner join addresses on addresses.address_id = orders.shipping_address_id
inner join deliveries d2 on d2.order_id = orders.order_id
INNER join delivery_trips dt on dt.delivery_trip_id  = d2.delivery_trip_id
inner join locations on locations.location_id  = addresses.location_id
where (date(orders.created_at) between ('."'".$fdate."'".') 
 and ('."'".$tdate."'".')) 
 and delivery_trip_type ="Custom"
group by(JSON_EXTRACT(location_name, "$.en")) order by area_count desc limit 10');

$statichagraph =\DB::select('select JSON_EXTRACT(location_name, "$.en") as area_name,count(JSON_EXTRACT(location_name, "$.en")) as area_count from orders
inner join addresses on addresses.address_id = orders.shipping_address_id
inner join deliveries d2 on d2.order_id = orders.order_id
INNER join delivery_trips dt on dt.delivery_trip_id  = d2.delivery_trip_id
inner join locations on locations.location_id  = addresses.location_id
where (date(orders.created_at) between ('."'".$fdate."'".') 
 and ('."'".$tdate."'".')) 
 and delivery_trip_type ="Static"
group by(JSON_EXTRACT(location_name, "$.en")) order by area_count desc limit 10');





try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Dynamic_Locations" =>   $dynamichagraph,
                                 "Custom_Locations" =>   $customhagraph,
                                 "Static_Locations" =>   $statichagraph,

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }
    public function HotAreasByOrders(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];

$hotareasbyorders =\DB::select('select JSON_EXTRACT(location_name, "$.en") as area_name,count(JSON_EXTRACT(location_name, "$.en")) as area_count from orders
inner join addresses on addresses.address_id = orders.shipping_address_id
inner join locations on locations.location_id  = addresses.location_id
where (date(orders.created_at) between ('."'".$fdate."'".') 
 and ('."'".$tdate."'".')) 
group by(JSON_EXTRACT(location_name, "$.en")) order by area_count desc limit 10');





try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Area_Wise_Count" =>   $hotareasbyorders,
                                 "Chart_Title" =>  'Area Wise Count',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }
    public function DeliveredCartonQuantityPerChannel(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];
   $checkstatus=$dates['activeCard'];
   if($checkstatus == 'ALL')
   {

    $cartonquantityperchannel= \DB::select("SELECT JSON_UNQUOTE(json_extract(channels.channel_name ,'$.en')) as channel_name ,
    date(orders.created_at) as order_date , SUM(order_items.quantity)  as total_quantity
    from orders
    inner join order_items
    on orders.order_id = order_items.order_id
    inner join customers
    on orders.customer_id = customers.customer_id
    inner join channels
    on customers.channel_id = channels.channel_id
    where (date(orders.created_at) BETWEEN ('$datefrom') AND ('$dateto'))
    group by customers.channel_id ,order_date,channel_name
    order by order_date ASC");
       $title='Total';

   }
   else
   {

    $getid=DB::table('order_statuses')
    ->where('key',$checkstatus)
    ->pluck('order_status_id');
 $getid=$getid[0];
    $cartonquantityperchannel= \DB::select("SELECT JSON_UNQUOTE(json_extract(channels.channel_name ,'$.en')) as channel_name ,
    date(orders.created_at) as order_date , SUM(order_items.quantity)  as total_quantity
    from orders
    inner join order_items
    on orders.order_id = order_items.order_id
    inner join customers
    on orders.customer_id = customers.customer_id
    inner join channels
    on customers.channel_id = channels.channel_id
    where
    order_status_id = $getid and
    (date(orders.created_at) BETWEEN ('$datefrom') AND ('$dateto'))
    group by customers.channel_id ,order_date,channel_name
    order by order_date ASC");
   $gettitle=DB::table('order_statuses')
   ->where('key',$checkstatus)
   ->pluck('order_status_title');
   $title=json_decode($gettitle[0])->en;


   }




try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Delivered_Carton_Quantity_Per_Channel" =>   $cartonquantityperchannel,
                                 "Chart_Title" =>  $title.' Quantity Per Channel',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }
    public function AveCartonPerDeliveredOrder(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];

   $checkstatus=$dates['activeCard'];
   if($checkstatus == 'ALL')
   {
   $avecartonperdeliveredorder= \DB::select("SELECT
   date(orders.created_at) as order_date , ROUND(AVG(order_items.quantity))  as Average_Carton,COUNT(orders.order_id)
   from orders
   inner join order_items
   on orders.order_id = order_items.order_id
   where (date(orders.created_at) BETWEEN ('$datefrom') AND ('$dateto'))
   group by order_date
   order by order_date ASC");
    $title='Total';

   }
   else
   {

    $getid=DB::table('order_statuses')
    ->where('key',$checkstatus)
    ->pluck('order_status_id');
 $getid=$getid[0];

    $avecartonperdeliveredorder= \DB::select("SELECT
    date(orders.created_at) as order_date , ROUND(AVG(order_items.quantity))  as Average_Carton,COUNT(orders.order_id)
    from orders
    inner join order_items
    on orders.order_id = order_items.order_id
    where order_status_id = $getid and
    (date(orders.created_at) BETWEEN ('$datefrom') AND ('$dateto'))
    group by order_date
    order by order_date ASC");
       $gettitle=DB::table('order_statuses')
       ->where('key',$checkstatus)
       ->pluck('order_status_title');
       $title=json_decode($gettitle[0])->en;



   }


try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Ave_Carton_Per_Delivered_Order" =>   $avecartonperdeliveredorder,
                                 "Chart_Title" => 'Ave Carton Per '. $title .' Orders',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }
    public function HomeDeliveryAveCartonQuantity(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];
   $checkstatus=$dates['activeCard'];
   if($checkstatus == 'ALL')
   {
   $homedeliveryavecartonquantity= \DB::select("SELECT
   date(orders.created_at) as order_date , SUM(order_items.quantity)  as total_quantity,COUNT(orders.order_id) as total_orders
   from orders
   inner join order_items
   on orders.order_id = order_items.order_id
   inner join customers
   on orders.customer_id = customers.customer_id
   inner join channels
   on customers.channel_id = channels.channel_id
   where channels.channel_id=1 and
   (date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
   group by customers.channel_id ,order_date
   order by order_date ASC");
   $title='Total';
   }
   else
   {
    $getid=DB::table('order_statuses')
    ->where('key',$checkstatus)
    ->pluck('order_status_id');
 $getid=$getid[0];
    $homedeliveryavecartonquantity= \DB::select("SELECT
    date(orders.created_at) as order_date , SUM(order_items.quantity)  as total_quantity,COUNT(orders.order_id) as total_orders
    from orders
    inner join order_items
    on orders.order_id = order_items.order_id
    inner join customers
    on orders.customer_id = customers.customer_id
    inner join channels
    on customers.channel_id = channels.channel_id
    where
    order_status_id = $getid and
    channels.channel_id=1 and
    (date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
    group by customers.channel_id ,order_date
    order by order_date ASC");
       $gettitle=DB::table('order_statuses')
       ->where('key',$checkstatus)
       ->pluck('order_status_title');
       $title=json_decode($gettitle[0])->en;

    
   }


try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Home_Delivery_Ave_Carton_Quantity" =>   $homedeliveryavecartonquantity,
                                 "Chart_Title" =>   'Home Delivery Ave Carton Quantity Per '.$title.' Orders',

                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }
    public function TotalOrderSummaryByCreationDate(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];
   $checkstatus=$dates['activeCard'];
   if($checkstatus == 'ALL')
   {
   $totalordersummarybycreationdate= \DB::select("SELECT JSON_UNQUOTE(json_extract(channels.channel_name ,'$.en')) as channel_name , COUNT(orders.order_id) as total_orders,date(orders.created_at) as order_date  from orders
   inner join customers
   on orders.customer_id = customers.customer_id
   inner join channels on customers.channel_id = channels.channel_id
   where (date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
   group by customers.channel_id ,order_date,channel_name
   order by order_date ASC");
     $title='Total';

   }
   else 
   {

    $getid=DB::table('order_statuses')
    ->where('key',$checkstatus)
    ->pluck('order_status_id');
 $getid=$getid[0];
    $totalordersummarybycreationdate= \DB::select("SELECT JSON_UNQUOTE(json_extract(channels.channel_name ,'$.en')) as channel_name , COUNT(orders.order_id) as total_orders,date(orders.created_at) as order_date  from orders
    inner join customers
    on orders.customer_id = customers.customer_id
    inner join channels on customers.channel_id = channels.channel_id
    where order_status_id = $getid
   and (date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
    group by customers.channel_id ,order_date,channel_name
    order by order_date ASC");
       $gettitle=DB::table('order_statuses')
       ->where('key',$checkstatus)
       ->pluck('order_status_title');
       $title=json_decode($gettitle[0])->en;

   }



try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Total_Order_Summary_By_Creation_Date" =>   $totalordersummarybycreationdate,
                                 "Chart_Title" => $title.' Orders Summary - By Creation Date',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }

    public function CartonQuantityMergedChannelByCreationDate(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];
   $checkstatus=$dates['activeCard'];
   if($checkstatus == 'ALL')
   {
   $cartonquantitymergedchannelbycreationdate= \DB::select("SELECT  JSON_UNQUOTE(json_extract(channels.channel_name ,'$.en')) as channel_name ,
   date(orders.created_at) as order_date , SUM(order_items.quantity)  as total_quantity
   from orders
   inner join order_items
   on orders.order_id = order_items.order_id
   inner join customers
   on orders.customer_id = customers.customer_id
   inner join channels
   on customers.channel_id = channels.channel_id
   where (date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
   group by customers.channel_id ,order_date,channel_name
   order by order_date ASC");
   $title='Total';
   }
   else 
   {
    $getid=DB::table('order_statuses')
    ->where('key',$checkstatus)
    ->pluck('order_status_id');
    $getid=$getid[0];
    $cartonquantitymergedchannelbycreationdate= \DB::select("SELECT  JSON_UNQUOTE(json_extract(channels.channel_name ,'$.en')) as channel_name ,
    date(orders.created_at) as order_date , SUM(order_items.quantity)  as total_quantity
    from orders
    inner join order_items
    on orders.order_id = order_items.order_id
    inner join customers
    on orders.customer_id = customers.customer_id
    inner join channels
    on customers.channel_id = channels.channel_id
    where order_status_id = $getid
   and (date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
    group by customers.channel_id ,order_date,channel_name
    order by order_date ASC");
 $gettitle=DB::table('order_statuses')
 ->where('key',$checkstatus)
 ->pluck('order_status_title');
 $title=json_decode($gettitle[0])->en;

   }



try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Carton_Quantity_Merged_Channel_By_Creation_Date" =>   $cartonquantitymergedchannelbycreationdate,
                                 "Chart_Title" =>  $title.' Quantity (Merged Channel) By Creation Date',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }

    public function HDOrderStatusByCreationDate(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];
   $checkstatus=$dates['activeCard'];
   if($checkstatus == 'ALL')
   {
   $HDOrderStatusByCreationDate= \DB::select("SELECT JSON_UNQUOTE(json_extract(os.order_status_title ,'$.en')) as Status ,
   date(orders.created_at) as order_date , count(orders.order_id)  as total_orders
   from orders 
   inner join order_statuses os
   on os.order_status_id = orders.order_status_id 
   inner join order_items 
   on orders.order_id = order_items.order_id 
   inner join customers 
   on orders.customer_id = customers.customer_id 
   inner join channels
   on customers.channel_id = channels.channel_id 
   where (date(orders.created_at) BETWEEN '2021-01-26' AND '2021-10-26')
   group by order_date,os.order_status_id,order_status_title
   order by order_date ASC");
      $title='Total';
   }
   else {
    $getid=DB::table('order_statuses')
    ->where('key',$checkstatus)
    ->pluck('order_status_id');
    $getid=$getid[0];
    $HDOrderStatusByCreationDate= \DB::select("SELECT JSON_UNQUOTE(json_extract(os.order_status_title ,'$.en')) as Status ,
    date(orders.created_at) as order_date , count(orders.order_id)  as total_orders
    from orders 
    inner join order_statuses os
    on os.order_status_id = orders.order_status_id 
    inner join order_items 
    on orders.order_id = order_items.order_id 
    inner join customers 
    on orders.customer_id = customers.customer_id 
    inner join channels
    on customers.channel_id = channels.channel_id 
    where
    order_status_id = $getid and
    (date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
    group by order_date,os.order_status_id,order_status_title
    order by order_date ASC");
   $gettitle=DB::table('order_statuses')
   ->where('key',$checkstatus)
   ->pluck('order_status_title');
   $title=json_decode($gettitle[0])->en;


   }



try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "HD_Order_Status_By_Creation_Date" =>   $HDOrderStatusByCreationDate,
                                 "Chart_Title" => 'HD '.$title. ' Orders Status - By Creation Date',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }

    public function OrderGeneralCount(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];

$ordergeneralcount= \DB::select("SELECT  count(orders.order_id)  as Total_Orders,
os.key
from orders 
inner join order_statuses os
 on os.order_status_id = orders.order_status_id 
 where
 orders.order_status_id in(3,4,5,6,7,13,14)
and
(date(orders.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))
group by os.key
order by os.sequence");
$Total_Orders=0;
$key='ALL';

for($i=0;$i<count($ordergeneralcount);$i++)
{
    $Total_Orders+=$ordergeneralcount[$i]->Total_Orders;
    $key="ALL";
    
}

$newordergeneralcount[] = (object) ['Total_Orders' => $Total_Orders ,'key' => $key];
for($i=0;$i<count($ordergeneralcount);$i++)
{

    $newordergeneralcount[] = (object) ['Total_Orders' => $ordergeneralcount[$i]->Total_Orders,'key' => $ordergeneralcount[$i]->key];

}



try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Order_General_Count" =>   $newordergeneralcount,
    
                                 "Chart_Title" => 'Order General Count',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }
     
    public function AvgOrdersClassified(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];
$checktripstatus=$dates['activeCard'];

if($checktripstatus=='ALL')

{
    $avgordersclassified =\DB::select(" select round(avg(total)) as average,DATE_FORMAT(trip_date, '%Y-%m-%d') as trip_date ,delivery_trip_type, count(delivery_trip_id) as count_of_trips,sum(total) as Sum_of_orders from (
        select count(order_id) as total,dt.trip_date  ,dt.delivery_trip_id,
        dt.delivery_trip_type from deliveries d
        inner join delivery_trips dt on d.delivery_trip_id = dt.delivery_trip_id
    where  (date(dt.trip_date) BETWEEN  ('$fdate') AND ('$tdate'))
    and dt.deleted_at is NULL
     group by dt.trip_date , dt.delivery_trip_type,dt.delivery_trip_id
        ) trips
        group by trip_date ,delivery_trip_type");
        $title='All';

}
else

{
    $getid=DB::table('trip_statuses')
->where('key',$checktripstatus)
->pluck('trip_status_id');
$getid=$getid[0];

    $avgordersclassified =\DB::select("select round(avg(total)) as average,DATE_FORMAT(trip_date, '%Y-%m-%d') as trip_date ,delivery_trip_type, count(delivery_trip_id) as count_of_trips,sum(total) as Sum_of_orders from (
        select count(order_id) as total,dt.trip_date  ,dt.delivery_trip_id,
        dt.delivery_trip_type from deliveries d
        inner join delivery_trips dt on d.delivery_trip_id = dt.delivery_trip_id
    where  (date(dt.trip_date) BETWEEN  ('$fdate') AND ('$tdate'))
     and dt.deleted_at is NULL
        and dt.trip_status_id = $getid
        group by dt.trip_date , dt.delivery_trip_type,dt.delivery_trip_id
        ) trips
        group by trip_date ,delivery_trip_type");
        $gettitle=DB::table('trip_statuses')
        ->where('key',$checktripstatus)
        ->pluck('trip_status_title');
        $title=json_decode($gettitle[0])->en;



}



try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Avg_Orders_Classified" =>   $avgordersclassified,
                                 "Chart_Title" =>  'Avg Orders Classified For '.$title.' Trip Type By Trip Date',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }

     
    public function CustomerActivity(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];

    $customeractivity =\DB::select("select count(order_id) as total_orders,created_at from order_logs o
    where  (date(o.created_at) BETWEEN  ('$fdate') AND ('$tdate'))
   and o.order_status_id = 5 group by CAST(created_at AS DATE),created_at");

try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Total_orders" =>   $customeractivity,
                                 "Chart_Title" =>  'Customer Activity (Placed)',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }

    public function StaffActivity(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];


    $staffactivity =\DB::select("select count(order_id) as total_orders,created_at from order_logs o
    where  (date(o.created_at) BETWEEN  ('$fdate') AND ('$tdate'))
   and o.order_status_id in (13,14) group by CAST(created_at AS DATE),created_at");

try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Total_orders" =>   $staffactivity,
                                 "Chart_Title" =>  'Staff Activity (Ready For Pickup & Assigned)',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }
    public function DriverActivity(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];


    $driveractivity =\DB::select("select count(order_id) as total_orders,created_at as status_delivered from order_logs o
    where  (date(o.created_at) BETWEEN  ('$fdate') AND ('$tdate'))
   and o.order_status_id in (4,6) group by CAST(created_at AS DATE),created_at");


try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Total_orders" =>   $driveractivity,
                                 "Chart_Title" =>  'Driver Activity (Delivered & Cancelled)',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }


    public function AvgCostClassified(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];
$checktripstatus=$dates['activeCard'];

if($checktripstatus=='ALL')

{
    $avgcostclassified =\DB::select("select round(avg(gas_cost)) as average,delivery_trip_type,DATE_FORMAT(trip_date, '%Y-%m-%d') as trip_date from delivery_trips dt
    where  (date(dt.trip_date) BETWEEN  ('$fdate') AND ('$tdate'))
    and dt.deleted_at is NULL
        and dt.trip_status_id = 1
        and dt.gas_cost is NOT NULL
        group by delivery_trip_type,trip_date");
        $title='All';

}
else

{
    $getid=DB::table('trip_statuses')
->where('key',$checktripstatus)
->pluck('trip_status_id');
$getid=$getid[0];

    $avgcostclassified =\DB::select("select round(avg(gas_cost)) as average,delivery_trip_type,DATE_FORMAT(trip_date, '%Y-%m-%d') as trip_date from delivery_trips dt
    where  (date(dt.trip_date) BETWEEN  ('$fdate') AND ('$tdate'))
    and dt.deleted_at is NULL
        and dt.trip_status_id = $getid
        and dt.gas_cost is NOT NULL
        group by delivery_trip_type,trip_date");
        $gettitle=DB::table('trip_statuses')
        ->where('key',$checktripstatus)
        ->pluck('trip_status_title');
        $title=json_decode($gettitle[0])->en;



}






try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Avg_Cost_Classified" =>   $avgcostclassified,
                                 "Chart_Title" =>  'Avg Cost Classified For '.$title.' Trips By Trip Date',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }

    public function AvgProductClassified(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];
$checktripstatus=$dates['activeCard'];

if($checktripstatus=='ALL')

{
    $avgproductclassified =\DB::select("SELECT round(avg(sumquantity)) as average,delivery_trip_type, trip_date
    from
        (
        select sum(quantity) as sumquantity,delivery_trip_type,dt.delivery_trip_id,DATE_FORMAT(trip_date, '%Y-%m-%d') as trip_date from delivery_trips dt
        inner join deliveries d2 on dt.delivery_trip_id = d2.delivery_trip_id
        inner join order_items oi on d2.order_id = oi.order_id
        where  (date(dt.trip_date) BETWEEN  ('$fdate') AND ('$tdate'))
        and dt.deleted_at is NULL
            group by dt.delivery_trip_id,delivery_trip_type,trip_date
             )
            total_result
            group by delivery_trip_type,trip_date
            order by trip_date");
        $title='All';

}
else

{
    $getid=DB::table('trip_statuses')
->where('key',$checktripstatus)
->pluck('trip_status_id');
$getid=$getid[0];

    $avgproductclassified =\DB::select("SELECT round(avg(sumquantity)) as average,delivery_trip_type, trip_date
    from
        (
        select sum(quantity) as sumquantity,delivery_trip_type,dt.delivery_trip_id,DATE_FORMAT(trip_date, '%Y-%m-%d') as trip_date from delivery_trips dt
        inner join deliveries d2 on dt.delivery_trip_id = d2.delivery_trip_id
        inner join order_items oi on d2.order_id = oi.order_id
        where  (date(dt.trip_date) BETWEEN  ('$fdate') AND ('$tdate'))
        and dt.deleted_at is NULL
        and dt.trip_status_id=$getid
            group by dt.delivery_trip_id,delivery_trip_type,trip_date
             )
            total_result
            group by delivery_trip_type,trip_date
            order by trip_date");
        $gettitle=DB::table('trip_statuses')
        ->where('key',$checktripstatus)
        ->pluck('trip_status_title');
        $title=json_decode($gettitle[0])->en;



}

try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Avg_Product_Classified" =>   $avgproductclassified,
                                 "Chart_Title" =>  'Avg Product Classified For '.$title.' Trips By Trip Date',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }
    public function DeliveredOrderLeadTime(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }

$fdate=$dates['date_from'];
$tdate=$dates['date_to'];


    $orderleadtime =\DB::select("select difference ,COUNT(order_id) as total_orders
    FROM (
    SELECT
    case when TIMESTAMPDIFF(MINUTE ,created_at,status_delivered) <= 24 then 'With_in_24_Hours'
         when TIMESTAMPDIFF(MINUTE ,created_at,status_delivered) > 24 and TIMESTAMPDIFF(MINUTE ,created_at,status_delivered) <= 48 then 'With_in_48_Hours'
         when TIMESTAMPDIFF(MINUTE ,created_at,status_delivered) > 48 and TIMESTAMPDIFF(MINUTE ,created_at,status_delivered) <= 72 then  'With_in_72_Hours'
         else 'greater_then_72'
         end as difference,
    order_id
    from orders
    where
    (date(created_at) BETWEEN  ('$fdate') AND ('$tdate')) and
    order_status_id = 4
    ) as diference_table
    GROUP by difference");
    $length=count($orderleadtime);











try
{ 
                 
                        
            $response = [
                "code"=>200,
                      
                                "data" => [
                                 "Delivered_Order_Lead_Time" =>   $orderleadtime,
                                 "Chart_Title" =>  'Delivered Order Lead Time',
                             

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
      
    }
    public function TripGeneralCount(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from']." 00:00:00";
   $dateto=$dates['date_to']." 23:59:59";









$tripgeneralcount= \DB::select("SELECT  count(delivery_trips.delivery_trip_id)  as Total_Trips,
trip_statuses.key
from delivery_trips 
inner join trip_statuses
 on trip_statuses.trip_status_id = delivery_trips.trip_status_id 
 where
(date(delivery_trips.trip_date) BETWEEN  ('$datefrom') AND ('$dateto'))
and delivery_trips.deleted_at is NULL
group by trip_statuses.key
order by trip_statuses.sequence");


$Total_Trips=0;
$key='ALL';
for($i=0;$i<count($tripgeneralcount);$i++)
{
    $Total_Trips+=$tripgeneralcount[$i]->Total_Trips;
    $key="ALL";
    
}
$newtripgeneralcount[] = (object) ['Total_Trips' => $Total_Trips ,'key' => $key];

for($i=0;$i<count($tripgeneralcount);$i++)
{

    $newtripgeneralcount[] = (object) ['Total_Trips' => $tripgeneralcount[$i]->Total_Trips,'key' => $tripgeneralcount[$i]->key];

}


try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                            
                                 "Trip_General_Count" =>   $newtripgeneralcount,
                                 "Chart_Title" => 'Trip General Count',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }


    public function OrderCoordinates(Request $request,$store_id)
    {
        $dates = json_decode($request->get("data"),true);
      

        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id'
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'code' => '300',
            'message' => 'Date Missing!'
        ]);
    }
    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   $datefrom=$dates['date_from'];
   $dateto=$dates['date_to'];


   $HDOrderStatusByCreationDate= \DB::select("select a.map_info,a.address,a.longitude as addresslongitude,a.latitude as addresslatitude,
   l2.longitude as locationlongitude,l2.latitude as locationlatitude,
   location_name,l2.location_id from addresses a
   inner join locations l2 on a.location_id = l2.location_id
   inner join orders o on o.shipping_address_id = a.address_id 
   where  (date(o.created_at) BETWEEN  ('$datefrom') AND ('$dateto'))");
if(count($HDOrderStatusByCreationDate)== 0)
{
    $delivery_data=[]  ;

}
else{
for ($i=0;$i<count($HDOrderStatusByCreationDate);$i++){
   if($HDOrderStatusByCreationDate[$i]->addresslatitude == NULL || $HDOrderStatusByCreationDate[$i]->addresslongitude == NULL ||$HDOrderStatusByCreationDate[$i]->addresslatitude == 0 || $HDOrderStatusByCreationDate[$i]->addresslongitude == 0)        
            
   {
       if(json_decode($HDOrderStatusByCreationDate[$i]->map_info,true)['latitude'] == 0 || json_decode($HDOrderStatusByCreationDate[$i]->map_info,true)['longitude'] == 0 || json_decode($HDOrderStatusByCreationDate[$i]->map_info,true)['latitude'] == NULL || json_decode($HDOrderStatusByCreationDate[$i]->map_info,true)['longitude'] == NULL)
       {
        
          $lat=$HDOrderStatusByCreationDate[$i]->locationlatitude;
          $long=$HDOrderStatusByCreationDate[$i]->locationlongitude; 

       }
       else
       {
                  $lat=json_decode($HDOrderStatusByCreationDate[$i]->map_info,true)['latitude'];
               $long= json_decode($HDOrderStatusByCreationDate[$i]->map_info,true)['longitude']; 
       }
   }
   else 
   {
       $lat=$HDOrderStatusByCreationDate[$i]->addresslatitude;
       $long=$HDOrderStatusByCreationDate[$i]->addresslongitude;
     
   }

   $delivery_data[] = [
    "lat" => $lat,
    "lng" => $long,
   ];


}
}


try
{ 
                 
                        
            $response = [
                                  "code" =>200,
                                "data" => [
                                 "Order_Coordinates" =>   $delivery_data,

                                 "Chart_Title" =>  'Geo distribution of delivered orders',
                               

                                  
                                 "error" => 'No Error'
                                 ],
                                 'message' => 'Successful',
                             ];

return response()->json($response);
}
catch(\Exception $ex)
{
    return response()->json([
        "code" => 500,
        "message" => $ex->getMessage()
    ]);
}   
        
    }


//CronJob Method For Live Monitoring Of Vehicles 
    public function updateVehicleLocation(Request $request, $limit)
    {

        $validator = Validator::make([
            
            
            'limit' => $limit
        ],[
      
        'limit' => 'required|int|min:1'
    ]);

    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
        $getTrackingData = \DB::select("SELECT DISTINCT (s.IMEI),Ignition,Movement,`GSM Signal`,s.Speed,RFID,iButton,Angle,
        `Total Odometer`,`Trip Odometer`,Latitude,Longitude,Altitude,`Battery Level`,Satellites
        FROM sensor_data s 
        order by IMEI DESC limit $limit"); 
    
$getTrackingData = json_decode(json_encode($getTrackingData), true);
for($i=0;$i<count($getTrackingData);$i++)
{

    \DB::table("vehicles")
    ->join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
    ->join('sensor_data', 'fm_devices.imei', '=', 'sensor_data.IMEI')
    ->whereNull('vehicles.deleted_at')
    ->update([
        'current_movement' => $getTrackingData[$i]['Movement'],
        'current_ignition' => $getTrackingData[$i]['Ignition'],
        'odometer_current_reading' => $getTrackingData[$i]['Total Odometer'],
        // 'engine_hours' => $getTrackingData[$i]['Engine Total Hours Of Operation'],
        'current_latitude' => $getTrackingData[$i]['Latitude'],
        'current_longitude' => $getTrackingData[$i]['Longitude'],
        // 'destination_latitude' => ?,
        // 'destination_longitude' => ?,
        'current_speed' => $getTrackingData[$i]['Speed'],
        'current_angle' => $getTrackingData[$i]['Angle'],
        'current_altitude' => $getTrackingData[$i]['Altitude'],
        'locked_statellites' => $getTrackingData[$i]['Satellites'],
        // 'pos' => ?,
        // 'srv' => ?,
        'last_updated_at' => date("Y-m-d H:i:s"),
    ]);

    \DB::table("fm_devices")
    ->join('sensor_data', 'fm_devices.imei', '=', 'sensor_data.IMEI')
    ->update([
        'connection_state' => $getTrackingData[$i]['GSM Signal'],
 ]);

}
return response()->json([
    "code" => 200,
    "message" => 'Vehicle Location Updated Successfully',

]);
    }

    public function updateDeviceStatus(Request $request, $limit)
    {

        $validator = Validator::make([
            
            
            'limit' => $limit
        ],[
      
        'limit' => 'required|int|min:1'
    ]);

    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   } 
        $getTrackingData = \DB::select("SELECT distinct IMEI,created_at as created_at
        FROM sensor_data
        group by IMEI
        order by IMEI DESC limit $limit"); 

$getTrackingData = json_decode(json_encode($getTrackingData), true);


for($i=0;$i<count($getTrackingData);$i++)
{

    $created_at=$getTrackingData[$i]['created_at'];

    $currenttime = Carbon::createFromFormat('Y-m-d H:s:i', date('Y-m-d H:s:i'));
    $created_at = Carbon::createFromFormat('Y-m-d H:s:i', $getTrackingData[$i]['created_at']);

    $totalDuration = $currenttime->diffInMinutes($created_at);

    if($totalDuration > 10)
    {
        \DB::table("fm_devices")
        ->join('sensor_data', 'fm_devices.imei', '=', 'sensor_data.IMEI')
        ->where('fm_devices.imei',$getTrackingData[$i]['IMEI'])
        ->update([
            'connection_state' => 0,
     ]);



    }

}
return response()->json([
    "code" => 200,
    "message" => 'Device Status Updated Successfully',

]);
    }



    public function sendsms()
    {

        $SMS = new \App\Notifications\SMS( [$mobile['mobile']], $SMS_to_customer );
          $code = $SMS->sendMessage();
    }


}
