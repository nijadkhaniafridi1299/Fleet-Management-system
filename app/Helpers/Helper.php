
<?php
use App\Model\DeliveryTrip as DeliveryTrip;
use  App\Model\Customer;
use  App\Model\Order;
use  App\Model\Variant;
use  App\Model\ChannelProductPricing;
use  App\Model\ServiceCategory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


function cleanNameString($string) {
    $symbols_data = "/[^\p{L}\p{N}\s\-_\[\]\.@\(\)%&]/u";
    $sqlwordlist = array('select','drop','delete','update',' or ','mysql', 'sleep');
    $value = preg_replace($symbols_data, '', $string);
    foreach ($sqlwordlist as $v)
        $value = preg_replace("/\S*$v\S*/i", '', $value);
    return $value;
}

function respondWithSuccess($data, $module, $request_log_id, $message="", $success_code = 200){
  return response()->json([
      "code" => $success_code, "success" => true, "request_log_id" => $request_log_id,
      "module" => $module, "message" => $message, "data" => $data
  ]);
}

function respondWithError($errors,$request_log_id,$error_code=500){
  $err_msg = "";
  foreach($errors as $err){ $err_msg .= (is_array($err)?implode(",",$err):$err).","; }
  $err_msg = rtrim($err_msg,",");
  return response()->json([
      "code" => $error_code, "success" => false, "request_log_id" => $request_log_id, "message" => $err_msg, "errors" => $errors
  ]);
}

function responseValidationError($message, $errors){

    return response([

        'status' => 'error',
        'code' => '400',
        'message' => $message,
        'data' => $errors

    ]);

}

function getIp(){
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip); // just to be safe
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    return $ip;
                }
            }
        }
    }
    return request()->ip(); // it will return server ip when no client ip found
}

function send_notification_FCM($notification_id, $title, $message,$type,$source, $additional_data=null) {
  if($notification_id != null){
       $reg_id = $notification_id;
       //Source 0 For Web 
       
       if($source==0){
           $dataArray = array(
             'reference_id' => 1,
             'key' => $type,
           );
           
           $message = [
             "to" => $reg_id,
             "data"=>[
               "message"  => $title,
               "body" => $message
             ]
            
           ];
           
           if($additional_data!=null){
             $message["data"] = $message["data"] + $additional_data;
           }
           $client = new GuzzleHttp\Client([
             'headers' => [
                 'Content-Type' => 'application/json',
                 'Authorization' => 'key=AAAAYk9ARIg:APA91bFRX9oVQLrtPSEDPZJirZNQnBbsopyuy7TZHmxehcdl3Br-o_eqjrzjKCPWQrk-US0jiS8JZdF9m0ZRKz5-95dBVKslhzSeEg98TuXXGH8OxYsIvXyaRru57NWzhqmA2fdCaDmI',
             ]
           ]);
       }
       //Source 1 For Mobile
       else if($source==1){
           $message = [
             "registration_ids" => array($notification_id),
             "notification" => [
                 "title" => $title,
                 "body" => $message,
             ]
           ];
           $client = new GuzzleHttp\Client([
             'headers' => [
                 'Content-Type' => 'application/json',
                 'Authorization' => 'key=AAAAYk9ARIg:APA91bFRX9oVQLrtPSEDPZJirZNQnBbsopyuy7TZHmxehcdl3Br-o_eqjrzjKCPWQrk-US0jiS8JZdF9m0ZRKz5-95dBVKslhzSeEg98TuXXGH8OxYsIvXyaRru57NWzhqmA2fdCaDmI',
             ]
           ]);
         }
         $response = $client->post('https://fcm.googleapis.com/fcm/send',
             ['body' => json_encode($message)]
         );
         
       }
 }

function updateCustomerCredit($customer_id){
    $customer = Customer::select('customer_id','parent_id')->with('child:customer_id,parent_id')->find($customer_id);
    $customer_array = [];
    if($customer){
      $parent_id = isset($customer['parent_id'])?$customer['parent_id']:0;
      $customer_array[] = [$customer_id];
      $customer_array[] = [$parent_id];
      if(isset($customer->child) && $customer->child != Null){
        foreach ($customer->child as $key => $value) {
          $customer_array[] = [$value['customer_id']];
        }
      }
      $orders = Order::where('order_status_id',5)->whereIn('customer_id',$customer_array)->sum('grand_total');
      if($orders != Null){
        if ($customer->account_type_id != 0 && $customer->account_type_id != 2) {
          if ($customer->current_balance > $orders) {
            $customer->current_balance -= $orders;
          }else {
            $customer->current_balance = 0;
          }
          $customer->save();
        }
        elseif ($customer->account_type_id == 2) {
          $customer = Customer::find($parent_id);
          if ($customer->current_balance > $orders) {
            $customer->current_balance -= $orders;
          }else {
            $customer->current_balance = 0;
          }
          $customer->save();
        }
      }
    }
    return true;
  }

//check If the user is admin
function checkIfAdmin($user_id){
    
    $user = \App\Model\User::with('group')->whereHas('group', function($query) {
              $query->where('role_key', 'ADMINISTRATOR');
            })->value('user_id');
    return $user_id == $user;

}




  
  //Get Orders For Order Listing
function getOrdersFiltered($type,$store_id,$fdate,$tdate,$assigned_to_cust){   
if($type == 'dynamic'){

$fdate = $fdate." 00:00:00";
$tdate=  $tdate." 23:59:59";

$orders =\DB::select("select distinct(orders.order_id),
order_number,required_start_date,customer_lot_id,ready_for_pickup,estimated_end_date,aqg_dropoff_loc_id,customer_dropoff_loc_id,pickup_address_id,
shipping_address_id,orders.order_status_id,latitude,longitude,
order_status_title,order_statuses.key,addresses.address,addresses.address_title
from orders left join addresses on addresses.address_id =  orders.pickup_address_id 
inner join order_statuses on order_statuses.order_status_id = orders.order_status_id
where (date(required_start_date) BETWEEN  ('$fdate') AND ('$tdate')) 
and (orders.order_status_id = 16) 
and (orders.customer_id IN ('$assigned_to_cust'))
order by orders.required_start_date");

    }elseif ( $type == 'custom') {

  
$orders =\DB::select("select distinct(orders.order_id),
order_number,required_start_date,estimated_end_date,customer_lot_id,ready_for_pickup,aqg_dropoff_loc_id,customer_dropoff_loc_id,pickup_address_id,
shipping_address_id,orders.order_status_id,latitude,longitude,
order_status_title,order_statuses.key,addresses.address,addresses.address_title
from orders left join addresses on addresses.address_id =  orders.pickup_address_id 
inner join order_statuses on order_statuses.order_status_id = orders.order_status_id
where (date(required_start_date) BETWEEN  ('$fdate') AND ('$tdate')) 
and (orders.order_status_id IN (16,17)) 
and (orders.customer_id IN $assigned_to_cust)
order by orders.required_start_date");

    }
return $orders;


}

function getOrders($type,$store_id,$fdate,$tdate){   
if($type == 'dynamic'){

$fdate = $fdate." 00:00:00";
$tdate=  $tdate." 23:59:59";

$orders =\DB::select("select distinct(orders.order_id),
order_number,required_start_date,customer_lot_id,ready_for_pickup,estimated_end_date,aqg_dropoff_loc_id,customer_dropoff_loc_id,pickup_address_id,
shipping_address_id,orders.order_status_id,latitude,longitude,
order_status_title,order_statuses.key,addresses.address,addresses.address_title
from orders left join addresses on addresses.address_id =  orders.pickup_address_id 
inner join order_statuses on order_statuses.order_status_id = orders.order_status_id
where (date(required_start_date) BETWEEN  ('$fdate') AND ('$tdate')) 
and (orders.order_status_id = 16) 
order by orders.required_start_date");

    }elseif ( $type == 'custom') {

  
$orders =\DB::select("select distinct(orders.order_id),
order_number,required_start_date,estimated_end_date,customer_lot_id,ready_for_pickup,aqg_dropoff_loc_id,customer_dropoff_loc_id,pickup_address_id,
shipping_address_id,orders.order_status_id,latitude,longitude,
order_status_title,order_statuses.key,addresses.address,addresses.address_title
from orders left join addresses on addresses.address_id =  orders.pickup_address_id 
inner join order_statuses on order_statuses.order_status_id = orders.order_status_id
where (date(required_start_date) BETWEEN  ('$fdate') AND ('$tdate')) 
and (orders.order_status_id IN (16,17)) 
order by orders.required_start_date");

    }
return $orders;


}
 function paginate($items, $perPage = null, $page = null, $options = [])
{
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
    $items = $items instanceof Collection ? $items : Collection::make($items);
    return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
}
function custom_paginate($data, $per_page = null)
{
    //Get current page form url e.g. &page=6
    $currentPage = LengthAwarePaginator::resolveCurrentPage();

    //Create a new Laravel collection from the array data
    $collection = new Collection($data);
    
    //Define how many items we want to be visible in each page
    // $per_page = (int) per_page();

    //Slice the collection to get the items to display in current page
    $currentPageResults = $collection->slice(($currentPage - 1) * $per_page, $per_page)->values();

    //Create our paginator and add it to the data array
    $data['results'] = new LengthAwarePaginator($currentPageResults, count($collection), $per_page);

    //Set base url for pagination links to follow e.g custom/url?page=6
    return $data['results']->setPath(request()->url());
}

//Products For Order Listing
function getServiceRequest($sendorderid){
  
  $items= \DB::select ("select osr.order_service_request_id,osr.quantity,osr.start_date,osr.days_count,
  osr.remarks,osr.is_client_approval_required,osr.is_govt_approval_required,sc.title
  from order_service_requests osr
  inner join service_category sc on osr.service_category_id = sc.service_category_id
  where osr.order_id = $sendorderid") ;
  
return $items;
   }


  //Cehck category key of the Order
  function checkOrderCatgeory($sendorderid){
    
    $category_key = \DB::select ("select c.key from categories c
    inner join orders od on od.category_id = c.category_id
    where od.order_id = $sendorderid") ;
    $category_key = json_encode($category_key[0]);
    $category_key = json_decode($category_key,true);
    
    return $category_key['key'];
    }

  //Cehck category pickup and dropoff editable values
  function getPickupDropoffEditables($category){
    
    $editables = \DB::select ("select c.is_pickup_editable, c.is_dropoff_editable from categories c
    where c.key = '$category'") ;
    
    return $editables;
    }

  //assets assigned to yard
  function yardAssignedAssets(){
    
    $transaction_source = getAssetTransactionSource('Yard');
    $assets = \App\Model\AssetInventory::where('assigned_to',$transaction_source)->where('assignee_id','!=',null)->pluck('asset_id');
    
    
    return $assets;
    }

  //get category name of the Order
  function getOrderCategoryName($sendorderid){
    
    $category_key = \DB::select ("select c.category_name from categories c
    inner join orders od on od.category_id = c.category_id
    where od.order_id = $sendorderid") ;
    $category_key = json_encode($category_key[0]);
    $category_key = json_decode($category_key,true);
    
    return $category_key['category_name'];
    }

  //Get Order Status ID
  function getStatusId($key){
    
    
    $status_id = \App\Model\OrderStatus::where('key', 'like', '%' . $key . '%')->value('order_status_id');
    return $status_id;
    }
  //Get Order Status ID
  function getStatusIds($key){
    
    
    $status_id = \App\Model\OrderStatus::whereIn('key', $key)->pluck('order_status_id');
    return $status_id;
    }

  //Get Vehicle Type ID
  function getVehicleTypeId($key){

      $vehicle_type_id = \App\Model\VehicleType::where('key', 'like', '%' . $key . '%')->value('vehicle_type_id');
      return $vehicle_type_id;
    }

    //Get ASSET TRANSACTION TYPE ID
  function getAssetTransactionType($key){
    $transaction_type_id = \App\Model\TransactionType::where('key', 'like', '%' . $key . '%')->value('transaction_type_id');
    return $transaction_type_id;
  }

    //Get ASSET TRANSACTION SOURCE
  function getAssetTransactionSource($key){
    $transaction_type_id = \App\Model\TransactionSource::where('key', 'like', '%' . $key . '%')->value('transaction_source_id');
    return $transaction_type_id;
  }

  function getTripStatus($deliverytripid){
    
    $trip_status_key = \DB::select ("select ts.key from trip_statuses ts
    inner join delivery_trips dt on dt.trip_status_id = ts.trip_status_id
    where dt.delivery_trip_id = $deliverytripid") ;
    $trip_status_key = json_encode($trip_status_key[0]);
    $trip_status_key = json_decode($trip_status_key,true);
    
    return $trip_status_key['key'];
    }

  //   //check Possible Transactions to be made
  // function checkPossibleTransactions($skip_ids){
  //   $transactions = \App\Model\Skip::whereIn('skip_id', $skip_ids)->with('asset_inventory:asset_id,assigned_to')->get()->toArray();
  //   return $transactions;
  // }

   //Order Materials
function getOrderMaterials($sendorderid){
  
  $materials= \DB::select ("select  om.material_id,om.weight ,u.unit ,om.unit as unit_id,
  om.remarks ,om.value,om.length,m2.name from order_material om 
  inner join material m2 on om.material_id = m2.material_id 
   inner join units u on om.unit = u.id
  where om.order_id = $sendorderid") ;
 
  
  return $materials;
   }

  function getOrderAssets($sendorderid){
    
    $temp_assigned_assets = \App\Model\OrderServiceRequest::where('order_id',$sendorderid)->where('temp_assets','!=',null)->pluck('temp_assets');
    
    $temp_array = Arr::collapse($temp_assigned_assets);
    $assets = \App\Model\AssetInventory::with('yard:store_id,store_name','service_category:service_category_id,title')->whereIn('asset_id',$temp_array)->get(['asset_id','title','assignee_id','service_category_id']);
    
    return $assets;
   }

   function getOrderSkipMaterial($sendorderid){
  
    $customer_id = \App\Model\Order::where('order_id',$sendorderid)->value('customer_id');
    $skip_material= \DB::select ("select m.material_id, m.name, s.skip_id, ia.title from order_service_requests osr 
    inner join skips s on s.skip_id = osr.skip_id 
    inner join inv_assets ia on ia.asset_id = s.asset_id 
    inner join material m on m.material_id = s.material_id 
    where osr.order_id = $sendorderid
    AND s.customer_id = $customer_id") ;
   
    
    return $skip_material;
     }


   function getParentCustomer($customer_id){
  
    $parent_customer_id = \App\Model\Customer::where('customer_id',$customer_id)->value('parent_customer_id');     
    return $parent_customer_id;
     }


   //Customer Addresses for Transfer Order
   function getOrderAddresses($sendorderid){
  
    $addresses = [];
    $category_key = \DB::select ("select c.key from categories c
    inner join orders od on od.category_id = c.category_id
    where od.order_id = $sendorderid") ;
    $category_key = json_encode($category_key[0]);
    $category_key = json_decode($category_key,true);

   
    if($category_key['key'] == "TRANSFER" ){

      $addresses= \DB::select ("select ad.address_id,ad.type, ad.address_title, ad.address, ad.latitude, ad.longitude from addresses ad
      left join orders od on od.customer_id = ad.customer_id
      where od.order_id = $sendorderid");

    }
    elseif( $category_key['key'] == "CWA"){

      $addresses= \DB::select ("select ad.address_id,ad.type, ad.address_title, ad.address, ad.latitude, ad.longitude from addresses ad
      left join orders od on od.customer_id = ad.customer_id
      join address_types a on a.address_type_id = ad.type
      where od.order_id = $sendorderid
      AND ad.deleted_at IS NULL
      AND ad.status = 1
      AND a.key = 'CORPORATE CUSTOMER'");

    }
    
    return $addresses;
     }

     function getLatLngPickAndStores($vehicle_id){

        $details = \App\Model\DeliveryTrip::where('vehicle_id',$vehicle_id)->orderBy('created_at','DESC')->get(['order_id','aqg_dropoff_loc_id']);
        $order_id = $details[0]['order_id'];
        $aqg_dropoff = $details[0]['aqg_dropoff_loc_id'];
        if($aqg_dropoff){
          $get_dropoff_lat_lng = \DB::select("select latitude, longitude from stores
         
          where store_id = $aqg_dropoff");
          
        }
        $get_dropoff_lat_lng = isset($get_dropoff_lat_lng) ? $get_dropoff_lat_lng : null;
        if(isset($order_id)){
          $get_lat_lng = \DB::select("select ad.latitude as pickup_latitude, ad.longitude as pickup_longitude from orders o
          inner join addresses ad on o.pickup_address_id = ad.address_id
          where o.order_id = $order_id");

        }
        $get_lat_lng = isset($get_lat_lng) ? $get_lat_lng : null;
        
        return [$get_dropoff_lat_lng,$get_lat_lng];
        

      }

      //Get Order Status
      function getOrderStatus($order_id){
    
        $order_status_key = \DB::select ("select os.key from order_statuses os
        inner join orders o on o.order_status_id = os.order_status_id
        where o.order_id = $order_id") ;
        $order_status_key = json_encode($order_status_key[0]);
        $order_status_key = json_decode($order_status_key,true);
        
        return $order_status_key['key'];
      }

   //Get AQG addresses
   function getAQGAddresses(){
   
      $addresses= \DB::select ("select s.store_id, s.store_name,s.address, s.latitude,store_name as address_title, s.longitude from stores s
                                where s.status = 1 AND deleted_at IS NULL") ;
      return $addresses;

    }
    

  //Get Order Locations 
  function getCustomerLot($customerlotid){

    $customerlotid = \App\Model\CustomerLot::where('customer_lot_id', $customerlotid)->value('lot_number');
    // $customerlotid=\DB::select("select lot_number from customer_lots where customer_lot_id  = $customerlotid");
    return $customerlotid;
  }

  //Get Customer Information 
  function getCustomerInfo($order_id){

    $customer_info = \DB::select("select c.customer_id as customer_id, c.name as customer_name from orders o
    inner join customers c on o.customer_id = c.customer_id
    where o.order_id = $order_id");
    $customer_info = json_encode($customer_info[0]);
    $customer_info = json_decode($customer_info,true);
    return $customer_info;
  }

   //Get Order Locations 
   function getAddress($sendaddressid){

        $address=\DB::select("select longitude,latitude,address, address_title
           from addresses where address_id  = $sendaddressid");
        return $address;
  }

    //Get AQG Locations 
    function getAQGAddress($sendaddressid){

      $address=\DB::select("select longitude,latitude,address, store_name as address_title
         from stores where store_id  = $sendaddressid");
      return $address;
}

    function getAddressCategories(){

      $address_categories = \DB::select("select address_type_id, name
         from address_types where deleted_at IS NULL");
      return $address_categories;
}

 

 

function getProduct($customer_id=0,$product_id,$type=1){

  if($type == 2){
    $prodPrice = Product::where('product_id', $product_id)->where('status', 1)->get()->first();
    // $channel = Channel::where('channel_code', 'Mosque')->get()->first();
    // $prodPrice = ChannelProductPricing::where('channel_id',$channel->channel_id)
    // ->where('product_id', $product_id)->where('status',1)->get()->first();
    return $prodPrice;
  }
  if($customer_id != 0){
    $customer = Customer::find($customer_id);
    if($customer['account_type_id'] == 1 || $customer['account_type_id'] == 4 || $customer['account_type_id'] == 2){
      if($customer['account_type_id'] == 2) {
        $parent = Customer::find($customer['parent_id']);
        if(is_object($parent)){
          if($parent->account_type_id == 1){
            $prodPrice = ChannelProductPricing::where('channel_id',$parent->channel_id)
            ->where('product_id', $product_id)->where('status',1)->get()->first();
            return $prodPrice;
          }
          elseif($parent->account_type_id == 4){
            $channel_products = \App\Model\ChannelProductPricing::select('product_id')->where('channel_id',$parent->channel_id)->where('status',1)->get()->toArray();
            $prodPrice = ChannelProductPricing::where('channel_id',$parent->sub_channel_id)
            ->where('product_id', $product_id)->where('status',1)->whereIn('product_id',$channel_products)->get()->first();
            return $prodPrice;
          }
          elseif($parent->account_type_id == 3){
            $channel_products = \App\Model\ChannelProductPricing::select('product_id')->where('channel_id',$parent->channel_id)->where('status',1)->get()->toArray();
            $sub_channel_products = \App\Model\ChannelProductPricing::select('product_id')->where('channel_id',$parent->sub_channel_id)->Where('status',1)->get()->toArray();
            $prod = [];
            foreach($sub_channel_products as $s){
              if(in_array($s,$channel_products)){
                array_push($prod,$s);
              }
            }

            $prodPrice = CustomerProductPricing::where('customer_id',$parent->customer_id)
            ->where('product_id', $product_id)->where('status',1)->whereIn('product_id',$prod)->get()->first();
            return $prodPrice;
          }
        }
      }
      if($customer['account_type_id'] == 4) {
        $channel_products = \App\Model\ChannelProductPricing::select('product_id')->where('channel_id',$customer['channel_id'])->where('status',1)->get()->toArray();
        $prodPrice = ChannelProductPricing::where('channel_id',$customer['sub_channel_id'])
        ->where('product_id', $product_id)->where('status',1)->whereIn('product_id',$channel_products)->get()->first();
        return $prodPrice;
      }
      else{
        $prodPrice = ChannelProductPricing::where('channel_id',$customer['channel_id'])
        ->where('product_id', $product_id)->where('status',1)->get()->first();
        return $prodPrice;

      }
    }
    elseif($customer['account_type_id'] == 3){
      $channel_products = \App\Model\ChannelProductPricing::select('product_id')->where('channel_id',$customer['channel_id'])->where('status',1)->get()->toArray();
      $sub_channel_products = \App\Model\ChannelProductPricing::select('product_id')->where('channel_id',$customer['sub_channel_id'])->Where('status',1)->get()->toArray();
      $prod = [];
      foreach($sub_channel_products as $s){
        if(in_array($s,$channel_products)){
          array_push($prod,$s);
        }
      }

      $prodPrice = CustomerProductPricing::where('customer_id',$customer['customer_id'])
      ->where('product_id', $product_id)->where('status',1)->whereIn('product_id',$prod)->get()->first();
      return $prodPrice;
    }
    elseif($customer['account_type_id'] == 0){
      $prodPrice = ChannelProductPricing::where('channel_id',$customer['channel_id'])
      ->where('product_id', $product_id)->where('status',1)->get()->first();
      return $prodPrice;
    }
  }
  else{
    $prodPrice = ChannelProductPricing::where('channel_id',1)
    ->where('product_id', $product_id)->where('status',1)->get()->first();
    return $prodPrice;
  }
}




    // function getVariant($customer_id=0,$product_id,$type=1,$variant_id){

    //     if($type == 2){
    //       $prodPrice = Variant::where('product_id', $product_id)->where('variant_id',$variant_id)->where('status', 1)->get()->first();
    //       // $channel = Channel::where('channel_code', 'Mosque')->get()->first();
    //       // $prodPrice = ChannelProductPricing::where('channel_id',$channel->channel_id)
    //       // ->where('product_id', $product_id)->where('status',1)->get()->first();
    //       return $prodPrice;
    //     }
    //     if($customer_id != 0){
    //       $customer = Customer::find($customer_id);
    //       if($customer['account_type_id'] == 1 || $customer['account_type_id'] == 4 || $customer['account_type_id'] == 2){
    //         if($customer['account_type_id'] == 2) {
    //           $parent = Customer::find($customer['parent_id']);
    //           if(is_object($parent)){
    //             if($parent->account_type_id == 1){
    //               $prodPrice = Variant::where('channel_id',$parent->channel_id)
    //               ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->get()->first();
    //               return $prodPrice;
    //             }
    //             elseif($parent->account_type_id == 4){
    //               $channel_products = ChannelProductPricing::select('product_id')->where('channel_id',$parent->channel_id)->where('status',1)->get()->toArray();
    //               $prodPrice = Variant::where('channel_id',$parent->sub_channel_id)
    //               ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->whereIn('product_id',$channel_products)->get()->first();
    //               return $prodPrice;
    //             }
    //             elseif($parent->account_type_id == 3){
    //               $channel_products = ChannelProductPricing::select('product_id')->where('channel_id',$parent->channel_id)->where('status',1)->get()->toArray();
    //               $sub_channel_products = ChannelProductPricing::select('product_id')->where('channel_id',$parent->sub_channel_id)->Where('status',1)->get()->toArray();
    //               $prod = [];
    //               foreach($sub_channel_products as $s){
    //                 if(in_array($s,$channel_products)){
    //                   array_push($prod,$s);
    //                 }
    //               }
      
    //               $prodPrice = Variant::where('customer_id',$parent->customer_id)
    //               ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->whereIn('product_id',$prod)->get()->first();
    //               return $prodPrice;
    //             }
    //           }
    //         }
    //         if($customer['account_type_id'] == 4) {
    //           $channel_products = ChannelProductPricing::select('product_id')->where('channel_id',$customer['channel_id'])->where('status',1)->get()->toArray();
    //           $prodPrice = Variant::where('channel_id',$customer['sub_channel_id'])
    //           ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->whereIn('product_id',$channel_products)->get()->first();
    //           return $prodPrice;
    //         }
    //         else{
    //           $prodPrice = Variant::where('channel_id',$customer['channel_id'])
    //           ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->get()->first();
    //           return $prodPrice;
      
    //         }
    //       }
    //       elseif($customer['account_type_id'] == 3){
    //         $channel_products = ChannelProductPricing::select('product_id')->where('channel_id',$customer['channel_id'])->where('status',1)->get()->toArray();
    //         $sub_channel_products = ChannelProductPricing::select('product_id')->where('channel_id',$customer['sub_channel_id'])->Where('status',1)->get()->toArray();
    //         $prod = [];
    //         foreach($sub_channel_products as $s){
    //           if(in_array($s,$channel_products)){
    //             array_push($prod,$s);
    //           }
    //         }
      
    //         $prodPrice = Variant::where('customer_id',$customer['customer_id'])
    //         ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->whereIn('product_id',$prod)->get()->first();
    //         return $prodPrice;
    //       }
    //       elseif($customer['account_type_id'] == 0){
    //         $prodPrice = Variant::where('channel_id',$customer['channel_id'])
    //         ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->get()->first();
    //         return $prodPrice;
    //       }
    //     }
    //     else{
    //       $prodPrice = Variant::where('channel_id',1)
    //       ->where('product_id', $product_id)->where('variant_id',$variant_id)->where('status',1)->get()->first();
    //       return $prodPrice;
    //     }
    //   }
 


        function _reArrangeOrders($output,$delivery_trip_id){
           
                        $i = 0;
                      
                        $vehicle = DB::table('vehicles')->where('vehicle_id',DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->pluck('vehicle_id'))->first();
                      
                        $vehicleAvgSpeed = ($vehicle->speed == NULL )?60:json_decode($vehicle->speed,true)['avg'];
                        $vehicleAvgSpeed = ($vehicleAvgSpeed*1000)/60;
                        $finalArray = array();
                        $total_trip_time = 0;
                        $total_trip_distance = 0;
                        $total_service_time = 0;
                   
                       
                        $ordersArrangement = __calculateDistance($output);
                 
        
                        $total_orders = count($output);
        
                      
        
                         $rearrageOrders = $ordersArrangement;
                        
                        $finalArray=$rearrageOrders;
                         
                        
                        $i = 0;
                        for(; $i <  count($finalArray);$i++) {
                         
                            
                            $time = ($finalArray[$i]['distance'] > 0)?($finalArray[$i]['distance'] /$vehicleAvgSpeed):0.00;
                            $orderDistances[] = [
                                "order_id" => $finalArray[$i]['id'],
                                "delivery_trip_id" => $delivery_trip_id,
                                "service_time" => 15,
                                "order_lat" => $finalArray[$i]['lat'],
                                "order_lng" => $finalArray[$i]['lng'],
                                "time_from_last_point" => round($time,2),
                                "distance_from_last_point" => round($finalArray[$i]['distance'],2)
                            ];
                            $total_trip_time += round($time,2);
                            $total_trip_distance += round($finalArray[$i]['distance'],2);
                            $total_service_time += 15;
                            
                        }
                       
                        
                           
                            
                            return ["deliveries" => $orderDistances,"total_trip_time" => $total_trip_time,"total_trip_distance" => $total_trip_distance, "total_service_time" => $total_service_time];
                    }

                    function __calculateDistance($data){
                        $orderDistances = array();
                   
            
                        foreach ($data as $key => $value) {
                           
                                
                                $lng = $value['order_lng'];
                                $lat = $value['order_lat'];
                             
                                if($lat == '' || $lng == '' || $lat == '0' || $lng == '0'){
                                    $distance = 0;
                                }
                                else{
                                    $distance = _getDistance($value['store_lat'],$value['store_lng'],$lat,$lng);
                                }
            
                        
                                $orderDistances[] = [
                                    "id" => $value['order_id'],
                                    "lat" => $lat,
                                    "lng" => $lng,
                                    "distance" => round($distance,2)
                                ];
            
                        }
                      
            
                        usort($orderDistances, function($a, $b) {
                             return $a['distance'] <=> $b['distance'];
                        });
                        return $orderDistances;
                }
            
                function _getDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
                 {
                 
                    if (($latitudeFrom == $latitudeFrom) && ($latitudeTo == $longitudeTo)) {
                        return 0;
                      }
                      else {

                        // $theta = $longitudeFrom - $longitudeTo;
                        // $dist = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) +  cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
                        // $dist = acos($dist);
                        // $dist = rad2deg($dist);
                        // $miles = $dist * 60 * 1.1515;
                    
  
                      
                        $miles=   \DB::select("SELECT( 3959 * acos( cos( radians($latitudeTo) ) * cos( radians( $latitudeFrom ) ) 
                        * cos( radians( $longitudeFrom ) - radians($longitudeTo) ) + sin( radians($latitudeTo) ) 
                        * sin( radians( $latitudeFrom ) ) ) ) as distance");
                        $count=count($miles);

                        if($count > 0)
                        {
                            $miles=$miles[0]->distance;
                        }
                        } 
                        return round($miles, 2);
                    }
                    function super_unique($array,$key)
    {
       $temp_array = [];
       foreach ($array as &$v) {
           if (!isset($temp_array[$v[$key]]))
           $temp_array[$v[$key]] =& $v;
       }
       $array = array_values($temp_array);
 
       
       return $array;

    }
    function _groupby($key, $data) {
      $result = array();
  
      foreach($data as $val) {
          if(array_key_exists($key, $val)){
              $result[$val[$key]][] = $val;
          }else{
              $result[""][] = $val;
          }
      }
  
      return $result;
  }
  function group_by($key, $data) {
    $result = array();

    foreach($data as $val) {
        if(array_key_exists($key, $val)){
            $result[$val[$key]][] = $val;
        }else{
            $result[""][] = $val;
        }
    }
        return $result;
}

function is_in_polygon($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)
{
  $i = $j = $c = 0;
  for ($i = 0, $j = $points_polygon ; $i < $points_polygon; $j = $i++) {
    if ( (($vertices_y[$i]  >  $latitude_y != ($vertices_y[$j] > $latitude_y)) &&
     ($longitude_x < ($vertices_x[$j] - $vertices_x[$i]) * ($latitude_y - $vertices_y[$i]) / ($vertices_y[$j] - $vertices_y[$i]) + $vertices_x[$i]) ) )
       $c = !$c;
  }
  return $c;
}  

function getContractTypes(){ 

  $contract_types= \DB::select("select id as contract_type_id,contract_type_title, `key`
  from contract_type where status = 1");
  return $contract_types;
}

function getContractStatuses(){ 

  $contract_status= \DB::select("select id as contract_status_id,contract_status_title, `key`
  from contract_status");
  return $contract_status;
}

// function getContractsList(){ 

//   $contracts_list= \DB::select("select contract_id, contract_number	, start_date, end_date, contract_type, balance_due as price
//   from contracts");
//   return $contracts_list;
// }

// function getContractLots(){ 

//   $contract_lots = \DB::select("select contract_id, contract_number	
//   from contracts");
//   return $contract_lots;
// }

function getUnits(){ 

  $contract_lots = \DB::select("select id, unit	
  from units where status = 1");
  return $contract_lots;
}

function unitConversion($value,$unit){ 

  $contract_lots = \DB::select("select id, unit	
  from units where status = 1");
  return $contract_lots;
}

function callExternalAPI($method,$url,$body,$headers,$params = null){
  $client = new GuzzleHttp\Client([ 'headers' => $headers ]);
  $response = null; $return_data = "";
  switch($method){
    case "POST": $request = $client->post($url, ['body' => json_encode($body)] ); $response = $request->getBody()->getContents(); 
    // $return_data .= $response->read(1024);
    $return_data = $response;
   

    break;
    default: 
    if($params == null){
     $request = $client->get($url); $response = $request->getBody(); 
     while (!$response->eof()) { $return_data .= $response->read(1024); }
     break;
    }
    else{
      $request = $client->get($url,$params); $response = $request->getBody(); 
     while (!$response->eof()) { $return_data .= $response->read(1024); }
     break;

    }

  }
  
 
  return json_encode($return_data);

}

function callExternalAPIwithContent($method,$url,$body,$headers,$params = null){
  $client = new GuzzleHttp\Client([ 'headers' => $headers ]);
  $response = null; $return_data = "";
  switch($method){
    // $sap_obj = new SapApi();
    // $sap_api = $sap_obj->add($sap_data);
    case "POST": $request = $client->post($url, ['body' => json_encode($body)] ); $response = $request->getBody()->getContents(); 
    return $response;

    break;
    default: 

  }
}