<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Order as Order;
use Auth;
use Validator;
use Illuminate\Validation\Rule;
class RoutingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    public function routingAndCapacityAction(Request $request,$store_id,$type,$fdate,$tdate){
     
     $validator = Validator::make(['fdate' => $fdate,'tdate' => $tdate , 'store_id' => $store_id,'type' => $type],[
     'fdate' => 'date|date_format:Y-m-d|nullable',
     'tdate' => 'date|date_format:Y-m-d|nullable',
     'store_id' => 'required|int|min:1',
     'type' => ['required',Rule::in(['custom', 'dynamic','static'])]
      ]);
         if ($validator->fails())
            {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
            }
      
         
          $user=Auth::user();
           $items = [];
            $ordersArray = [];
    
    $assigned_to_cust = [];
    if(!Auth::guard('oms')->check()){
        $user = (Auth::user());
        $user_id = ($user->user_id);  
        $assigned_to_cust = \App\Model\Customer::where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id)->orWhere('dispatcher_id',$user_id)->pluck('customer_id')->toArray();
      
        $ids = join(",",$assigned_to_cust);
        $ids = '('.$ids.')';
    if(count($assigned_to_cust) > 0){
        //Get Orders In Selected Date
        $orders = getOrdersFiltered($type,$store_id,$fdate,$tdate,$ids);
        goto skipline;
    }
    
    }
        $orders = getOrders($type,$store_id,$fdate,$tdate);

        skipline:
        

    //Get Cities Data 
    $cities=\DB::select("select location_id,location_name,location_level_id,entry_time,exit_time,delay
    from locations");
  $cityArray=[];
   $scaleArray=[];
if(count($cities)>0)
{
    $cities = json_decode( json_encode($cities),true);
    foreach ($cities as $key => $city) {
        if($city['location_level_id']==1)
        {
        $cityArray[] = [
            "location_id" => $city['location_id'],
            "entry_time" => $city['entry_time'],
            "exit_time" => $city['exit_time'],
            "location_name" => json_decode($city['location_name'],true), 
           
        ]; 
    }
    else if($city['location_level_id']==2)
    {
       
        $scaleArray[] = [
            "location_id" => $city['location_id'],
            "delay" => $city['delay'],
            "location_name" => json_decode($city['location_name'],true), 
           
        ]; 

    }

    }
}

    if(count($orders)>0){
        $orders = json_decode( json_encode($orders),true);
      
        foreach ($orders as $key => $value) {
       
            $sendorderid=$value['order_id'];
       
        //   Get Products Against Each Order
           $items = getServiceRequest($sendorderid);
       
           $items = json_decode(json_encode($items), true);
    
           if(count($items)>0) {
           
            $itemsarray=[];
               foreach ($items as $key => $item) {

                    $itemsarray[$key] = [
                       "order_service_request_id" => $item['order_service_request_id'],
                       "title" => json_decode($item['title'],true), 
                       "quantity" => $item['quantity'],
                       "start_date" => $item['start_date'],
                       "days_count" => $item['days_count'],   
                       "remarks" => $item['remarks'],   
                       "is_client_approval_required" => $item['is_client_approval_required'],   
                       "is_govt_approval_required" => $item['is_govt_approval_required'],   
                       "is_govt_approval_required" => $item['is_govt_approval_required'],   
                      
                   ]; 
                   
               }
           }
           else 
           {
               $itemsarray=[];
           }
           //Get Materials Against Orders
       $orderMaterials = getOrderMaterials($sendorderid);
       $orderAssets = getOrderAssets($sendorderid);
       
    //    //Calculate Total weight of Order's Material
    //    $total_material_weight = \App\Model\OrderMaterial::where('order_id',$sendorderid)->sum('weight');
    //    $total_material_weight = isset($total_material_weight) && $total_material_weight != null ? $total_material_weight : 0;
       //DropOff locations list against each order based on order category and customer 08-15-2022
       $customer_sites = getOrderAddresses($sendorderid);
       $aqg_addresses = getAQGAddresses();
       $skip_material = getOrderSkipMaterial($sendorderid);
       $category = checkOrderCatgeory($sendorderid);
       $category_name = getOrderCategoryName($sendorderid);
       $address_types = getAddressCategories();
       $address_types = isset($category) && $category == "SKIP_COLLECTION" ? [] : $address_types;
       $customer_details = getCustomerInfo($sendorderid);
       if($category == "ASSET"){
        $ready_for_pickup = Order::where('order_id',$sendorderid)->value('ready_for_pickup');
       }
       

       $orderMaterials = json_decode(json_encode($orderMaterials), true);
       $skip_material = json_decode(json_encode($skip_material), true);
       $orderAssets = json_decode(json_encode($orderAssets), true);
    
           if(count($orderMaterials)>0) {
           
     
            $orderMaterialsArray=[];
               foreach ($orderMaterials as $key => $item) {
                    $orderMaterialsArray[$key] = [
                       "material_id" => $item['material_id'],
                       "name" => json_decode($item['name']),   
                       "weight" => $item['weight'],
                       "unit" => json_decode($item['unit']),
                       "unit_id"=>$item['unit_id'],
                       "remarks" => $item['remarks'],
                       "value" => $item['value'],   
                       "length" => $item['length'],   
                  
                       
                      
                   ]; 
                   
               }
              
           }
           else 
           {
               $orderMaterialsArray=[];
           }

           if(count($skip_material) > 0) {
           
     
            $skip_material_array=[];
               foreach ($skip_material as $skip_key => $skip_item) {
                    $skip_material_array[$skip_key] = [
                       "material_id" => $skip_item['material_id'],
                       "name" => json_decode($skip_item['name']),   
                       "skip_id" => $skip_item['skip_id'],
                       "skip_title" => $skip_item['title'],
                       "weight" => 0,
                       "unit" => "",
                       "unit_id"=>"",
                       "remarks" => "",
                       "value" => "",   
                       "length" => 0,   
                  
                       
                      
                   ]; 
                   
               }
               $orderMaterialsArray = array_merge($orderMaterialsArray,$skip_material_array);

           }
           else 
           {
               $skip_material_array=[];
           }
           //Get Delivery Addresses Against Orders 
           $sendaddressid = null;
           $address = null;     
           if(isset($value['customer_dropoff_loc_id'] ))
          {
        
            $sendaddressid=$value['customer_dropoff_loc_id'];
            $address = getAddress($sendaddressid);
            $address = json_decode(json_encode($address), true);
      
          }
          else if (isset($value['aqg_dropoff_loc_id'] ))
                    {
            $sendaddressid=$value['aqg_dropoff_loc_id'];
            $address = getAQGAddress($sendaddressid);
            $address = json_decode(json_encode($address), true);
            
        
          }

          if(isset($category) && $category != null && $category == "ASSET"){
            $assets_info = getOrderAssets($sendorderid);
            foreach ($assets_info as $key => $item) {
                $item['title'] = isset($item['title']) ? $item['title'] : null;
                $item['yard_name'] = isset($item['yard']) && isset($item['yard']['store_name']) ? $item['yard']['store_name'] : null;
                $item['service_category_title'] = isset($item['service_category']) && isset($item['service_category']['title']) ? json_decode($item['service_category']['title'],true) : null;
                $item['service_category_title_en'] = $item['service_category_title'] != null ? $item['service_category_title']['en'] : null;
                $item['service_category_title_ar'] = $item['service_category_title'] != null ? $item['service_category_title']['ar'] : null;

                $name['en'] = $item['service_category_title_en']." -- ".$item['title']." -- ".$item['yard_name'];
                $name['ar'] = $item['service_category_title_ar']." -- ".$item['title']." -- ".$item['yard_name'];
                $orderMaterialsArray[$key] = [
                   "material_id" => $item['asset_id'],
                   "name" => $name,   
                   "weight" => 0,
                   "unit" => "",
                   "unit_id"=>0,
                   "remarks" => "",
                   "value" => 0,   
                   "length" => 0,   
              
                   
                  
               ]; 
               
           }
            
          }

          ##To make Drop-Off location mandatory while creating trips rather than while creating order, since dropoff location may vary depending on trips
        //   else {
        //     return response()->json([
        //         "code" => 204,
        //         'message' => 'Dropoff Location Does Not Exist For Order ID # '. $value['order_id'],
        //     ]);
        //   }

          //Get Customer Lot
          if(isset($value['customer_lot_id'] ))
          {
            $customerlotid = $value['customer_lot_id'];
            $customerlot = getCustomerLot($customerlotid); 

            // return $customerlot;
            $array = [
                "id" => $customerlotid,
                "lot_number" => isset($customerlot) ? $customerlot : ""
              ];
          }
 
         
            $ordersArray[] = [
              
                   "order_id" => $value['order_id'],
                   "order_number" => $value['order_number'],   
                   "category" => $category,   
                   "category_name" => $category_name,   
                   "customer_id" => $customer_details['customer_id'],   
                   "customer_name" => $customer_details['customer_name'],   
                   "required_start_date"=>date_format(date_create($value['required_start_date']),"Y-m-d"),
                   "estimated_end_date"=>date_format(date_create($value['estimated_end_date']),"Y-m-d"),
                   "pickup_address_id" => $value['pickup_address_id'] != null ? $value['pickup_address_id'] : $value['shipping_address_id'],
                   "ready_for_pickup" => $value['ready_for_pickup'],
                //    "total_material_weight" => $total_material_weight,
                   "order_status_id" => $value['order_status_id'],   
                     "order_status_title" => json_decode($value['order_status_title'],true),
                     "key" => $value['key'],
                     "latitude" => $value['latitude'],
                     "longitude" => $value['longitude'],
                    "address" => $value['address'],
                    "address_title" => isset($value['address_title']) ? $value['address_title'] : null,
                    "lot" => isset($customerlotid) ? $array : null,
                    "dropoff_address" => [
                        "id" => isset($sendaddressid) ? $sendaddressid : "",
                        "value" => isset($sendaddressid) ? $sendaddressid : "",
                        "latitude" => isset($address[0]['latitude']) ? $address[0]['latitude'] : "",
                        "longitude" => isset($address[0]['longitude']) ? $address[0]['longitude'] : "",
                        "address" => isset($address[0]['address']) ? ($address[0]['address']) : "",
                        "label" => isset($address[0]['address_title']) ? ($address[0]['address_title']) : "",
                    ] ,
                    "service_requested" => $itemsarray,
                    "order_materials" => $orderMaterialsArray,
                    "order_assets" => $orderAssets,
                    "customer_sites" => $customer_sites,
                    "address_types" => $address_types
                    

                ];
        }
        return response()->json([
            "code" => 200,
            "data" => [
            'orders' => $ordersArray,
            'cities' => $cityArray,
            'scales' => $scaleArray,
            'aqg_addresses' => $aqg_addresses
            ],
            'message' => 'Orders Loaded!'
        ]);
    }
    return response()->json([
        "code" => 404,
        "data" => [
            'orders' => []
        ],
        'message' => 'No Data Loaded'
    ]);
}
}
