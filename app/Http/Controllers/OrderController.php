<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use  App\Model\Product;
use  App\Model\Category;
use  App\Model\Skip;
use Illuminate\Support\Facades\Mail;
use  App\Model\Location;
use  App\Model\Unit;
use  App\Model\DeliverySlot;
use  App\Model\DeliveryTrip;
use  App\Model\Option;
use  App\Model\Customer;
use  App\Model\DocumentType;
use  App\Notification\PushNotification;
use Carbon\Carbon;
use DB;
use DateInterval;
use Illuminate\Support\Arr;
// use App\Mail\DemoEmail;
use  App\Model\CompanyWarehouse;
use  App\Model\Order;
use App\Model\ExtCompContract ;
use App\Model\Promocode ;
use App\Model\OrderLogs ;
use App\Model\Planning ;
use App\Model\SimilarProduct ;
use App\Model\OrderStatus ;
use App\Model\CustomerPaymentReg ;
use App\Model\Store ;
use App\Model\MobileAddress ;
use App\Model\Address ;
use App\Model\SaveOrder ;
use DateTime;
use App\Model\MobilePayment ;
use App\Model\AssetInventory ;
use App\Model\OrderItem ;
use App\Model\OrderHistory ;
use App\Model\PlanningDocumentation ;
use App\Model\User ;
use App\Model\Tool ;
use App\Model\Labor ;
use App\Model\Material ;
use App\Model\Designation ;
use App\Model\Vehicle ;
use App\Model\OrderServiceRequest ;
use App\Model\OrderServiceRequestHistory ;
use App\Model\ServiceCategory ;
use App\Model\OrderMaterial ;
use App\Model\OrderMaterialHistory ;
use App\Model\OrderLog ;
use App\Model\CustomerLot ;
use Auth;
// use Illuminate\Support\Facades\Hash;
use Validator;
use App\Payment\HyperPay;
use App\Http\Middleware\SaveMobileRequest as Mobile;
use App\Model\MobileOrder as GetOrder;
use Modules\Services\Http\Controllers\Erp\Internal\ErpOrderController;
use App\Model\PickupMaterial;
use App\Model\DropoffMaterial;


class OrderController extends Controller
{


  function placeOrder(Request $request){
    
    $data = json_encode(request()->post());
    $data =  json_decode($data,true);

    $history_data = $data;

    // $customer_id = \App\Model\Customer::getLoggedInCustomer();
    

    // if($customer_id){
    //   $customer_id = $customer_id['customer_id'];
    // }
    // else{
    //   $customer_id= null;
    // }
    $customer = Customer::find($data['user_id']);


    $mobile = new Mobile();
    $orderModal = new GetOrder();


    $validator = Validator::make($request->all(), [
      'user_id' => 'required|integer',
      'admin_id' => 'nullable|integer|exists:users,user_id',
      'category_id' => 'required|integer',
      'customer_lot_id' => 'nullable|numeric|exists:customer_lots,customer_lot_id',
      'estimated_weight' => 'nullable|numeric',
      'unit' => 'required_with:estimated_weight,nullable|numeric|exists:units,id',
      'no_of_trucks' => 'nullable|integer',
      'aqg_loc_id' => 'nullable|integer',
      'customer_dropoff_loc_id' => 'nullable|integer|exists:addresses,address_id',
      'addressData.ord_address_id' => 'required|integer|exists:addresses,address_id',
      'is_segregation_required' => 'nullable|boolean',
      'is_collection_required' => 'nullable|boolean',
      'start_date' => 'nullable|date',
      'end_date' => 'nullable|date',
      'comments' => 'nullable|string',
      'contact_person' => 'nullable|string',
      'department_name' => 'nullable|string',
      'contract_work_permit' => 'nullable|string',
      'disposal_type' => 'nullable|exists:disposal_types,id',
      'selected_material.*.weight' => 'nullable|numeric',
      'selected_material.*.unit' => 'required_with:selected_material.*.weight|nullable|numeric|exists:units,id',
      'selected_material.*.customer_lot_id' => 'nullable|numeric|exists:customer_lots,customer_lot_id',
      'skips.*.skip_id' => 'nullable|exists:skips,skip_id',
      'skips.*.replace' => 'nullable|boolean',
      'equipment.*.category' => 'required',
      'equipment.*.sub_category' => 'required|exists:service_category,service_category_id'
      
    ]);

    if ($validator->fails()) {
      return responseValidationError('Fields Validation Failed.', $validator->errors());
    }

    if(isset($data['iot_request']) && $data['iot_request'] == 1){
    
    }
    else{
      $user = auth()->guard('oms')->user();
      if($user->customer_id != $data['user_id']){
        return response()->json([
          "Code" => 403,
          "data" => "",
          "Message" => __("Unauthorized User.")
        ]);
      }
    }

    

  // If customer location is assigned, agq location will be zero
    if((isset($data['aqg_loc_id']) && $data['aqg_loc_id'] != "") || (isset($data['customer_dropoff_loc_id']) && $data['customer_dropoff_loc_id'] != "")){
      if($data['customer_dropoff_loc_id'] > 0){
        $data['aqg_loc_id'] = 0;
      }
    }



    // to change transaction key
    $responseData = '';

    if(isset($data['addressData']['ord_address_id'])){
      if(isset($data['addressData']['ord_address_id']))
      {
        if(empty($data['addressData']['ord_address_id']))  // Create Address
        {
          $source = 0;
          if(isset($data['order_source_id']) && !empty($data['order_source_id'])){
            $source = $data['order_source_id'];
          }
          $data['addressData']['ord_address_id'] = $this->NewAddressWithOrder($data['addressData'],$data['user_id'],$source,$app_version,$device,$data['floor_no'],$data['house_no']);
        }
      }
    } else  // Get last address
    {
      $result = GetOrder::with('address')->where("customer_id", $data['user_id'])->orderBy('order_id', 'desc')->first();
      $data['addressData']['ord_address_id'] = $result['shipping_address_id'];
    }

    if(!isset($data['promocode']) || $data['promocode'] == "")
    {
      $data['promocode'] = "";
      $channel_id = $customer->sub_channel_id;
      if($customer->account_type_id == 2){
        $parent = Customer::find($customer->parent_id);
        $channel_id = $parent->sub_channel_id;
      }
      $default_promo = Promocode::where('is_default',1)
        ->where('start_date', '<=', date('Y-m-d H:i:s'))
        ->where('end_date', '>=', date('Y-m-d H:i:s'))
        ->where("status",1);
      if(isset($data['addressData']['add_type']) && $data['addressData']['add_type'] == 2){
        $channel = \App\Model\Channel::where('channel_code', 'Mosque')->get()->first();
        $default_promo->where(function ($query) use ($channel) {
          $query->whereJsonContains("channels",$channel->channel_id)
            ->orWhereJsonLength('channels', 0);
          });
        }
        else{
          $default_promo->where(function ($query) use ($channel_id) {
          $query->whereJsonContains("channels",$channel_id)
          ->orWhereJsonLength('channels', 0);
        });
        // $promocode->whereJsonContains("channels","=",$channel_id);     
      }
      $default_promo = $default_promo->get()->first();

      if($default_promo){
        $count = 0;
        foreach ($data['cart'] as $key => $value) {
          $count = $count+$value['count'];
        }
        if($count >= $default_promo->min_quantity){
          $data['promocode'] = $default_promo->promo_code_access;
        }
      }
    }

    $history_data['net_weight'] = isset($data['estimated_weight']) ? $data['estimated_weight'] : null;
    $history_data['required_vehicles'] = isset($data['no_of_trucks']) ? $data['no_of_trucks'] : null;
    $history_data['user_id'] = 0;
    if(isset($data['iot_request']) && $data['iot_request'] == 1){
    }else{
      $user = auth()->guard('oms')->user();
      $history_data['customer_id'] = $user->customer_id;
    }

    $history_data['order_status_id'] = 5;
    $history_data['pickup_address_id'] = isset($data['addressData']['ord_address_id']) ? $data['addressData']['ord_address_id'] : null;

    
    $order = new saveOrder();

    if(isset($data['category_id']) ){
      $category_key = Category::where('category_id' , $data['category_id'])->value('key');
      if(isset($category_key) && $category_key == "ASSET"){
        $data['shipping_address_id'] = $data['addressData']['ord_address_id'];
        $data['customer_dropoff_loc_id'] = $data['addressData']['ord_address_id'];
        $history_data['shipping_address_id'] = $data['shipping_address_id'] = $data['addressData']['ord_address_id'];
        $data['addressData']['ord_address_id'] = null;

        $now = date('Y-m-d H:i:s');
        
        $data['start_date'] = $now;
      } 
    }
    
   
      if($category_key == "ASSET" || $category_key == "SKIP_COLLECTION"){
        $now = date('Y-m-d H:i:s');      
        $data['start_date'] = $now;
      }
    

    



    $param = [
      "cart" => $data['cart'],
      "promocode" => $mobile->clean_sqlwords($data['promocode']),
      "customer_lot_id" => (isset($data['customer_lot_id']) && $data['customer_lot_id'] != "") ? $data['customer_lot_id'] : Null,
      "user_id" => $mobile->clean_sqlwords($data['user_id']),
      "shipping_address_id" => isset($data['shipping_address_id']) ? $data['shipping_address_id'] : null,
      "address_id" => isset($data['addressData']['ord_address_id']) ? $data['addressData']['ord_address_id'] : null,
      "aqg_loc_id" => (isset($data['aqg_loc_id']) && $data['aqg_loc_id'] != "") ? $data['aqg_loc_id'] : Null,
      "customer_dropoff_loc_id" => (isset($data['customer_dropoff_loc_id']) && $data['customer_dropoff_loc_id'] != "") ? $data['customer_dropoff_loc_id'] : Null,
      // "payment_method" => $data['payment_type'],
      "payment_id" => (isset($data['transaction_id']) && $data['transaction_id'] != "") ? $data['transaction_id'] : Null,
      "order_source" => (isset($data['order_source_id'])) ? $data['order_source_id'] : $mobile->getDeviceType(),
      "is_wallet" => (isset($data['include_wallet']) && $data['include_wallet'] == 1) ? 1 : 0,
      "drop_at_gate" => (isset($data['drop_and_go']) && $data['drop_and_go'] == 1) ? 1 : 0,
      "order_note" => (isset($data['order_note']) && $data['order_note'] != '') ? $mobile->clean_sqlwords($data['order_note']) : '',
      "stc_otp" => (isset($data['stc_otp']) && $data['stc_otp'] == 1) ? 1 : 0,
      "department_name" => (isset($data['department_name']) && $data['department_name'] != "") ? $data['department_name'] : Null,
      "dpc" => (isset($data['dpc']) && $data['dpc'] != "") ? $data['dpc'] : Null,
      "contact_person" => (isset($data['contact_person']) && $data['contact_person'] != "") ? $data['contact_person'] : Null,
      "phone" => (isset($data['phone']) && $data['phone'] != "") ? $data['phone'] : Null,
      "log_in_id" => (isset($data['log_in_id']) && $data['log_in_id'] != "") ? $data['log_in_id'] : Null,
      "disposal_type" => (isset($data['disposal_type']) && $data['disposal_type'] != "") ? $data['disposal_type'] : Null,
      "net_weight" => (isset($data['estimated_weight']) && $data['estimated_weight'] != "") ? $data['estimated_weight'] : Null,
      "unit" => (isset($data['unit']) && $data['unit'] != "") ? $data['unit'] : Null,
      "no_of_vehicles" => (isset($data['no_of_trucks']) && $data['no_of_trucks'] != "") ? $data['no_of_trucks'] : Null,
      "contract_work_permit" => (isset($data['contract_work_permit']) && $data['contract_work_permit'] != "") ? $data['contract_work_permit'] : Null,
      "required_start_date" => (isset($data['start_date']) && $data['start_date'] != "") ? $data['start_date'] : Null,
      "estimated_end_date" => (isset($data['end_date']) && $data['end_date'] != "") ? $data['end_date'] : Null,
      "is_segregation_required" => (isset($data['is_segregation_required']) && $data['is_segregation_required'] != "") ? $data['is_segregation_required'] : Null,
      "is_collection_required" => (isset($data['is_collection_required']) && $data['is_collection_required'] != "") ? $data['is_collection_required'] : Null,
      "comments" => (isset($data['comments']) && $data['comments'] != "") ? $data['comments'] : Null,
      "category_id" => (isset($data['category_id']) && $data['category_id'] != "") ? $data['category_id'] : Null,
      "category_key" => (isset($category_key) && $category_key != "") ? $category_key : Null,
      "site_location" => (isset($data['site_location']) && $data['site_location'] != "") ? $data['site_location'] : Null,
      "created_by" => isset($data['admin_id']) ? $data['admin_id'] : null,
    ];
    

    $getOrder = isset($data['selected_material']) ? $order->create($param,$data['selected_material']) : $order->create($param);
    $order_id = $getOrder['order_id'];


    try {
      if(isset($data['selected_material']) && !empty($data['selected_material'])){
        foreach($data['selected_material'] as $item){
          OrderMaterial::updateOrCreate(['order_id' => $order_id,'material_id' => $item['value']],
                                        ['weight' => $item['weight'],'value' => $item['value'],'unit' => $item['unit'] , 'remarks' => $item['remarks'] ,'status' => 1,'customer_lot_id' => isset($item['customer_lot_id']) ? $item['customer_lot_id'] : null]);
        
        }
      }
    }catch (\Exception $ex) {
      $response = [
          "code" => 500,
          "data" => [
              "error" => $ex->getMessage()
          ],
          'message' => 'Error updating order material.'
      ];
      return response()->json($response);
    }

    if(isset($data['assets']) && $data['assets'] != ""){
    
      $assets = new OrderServiceRequest();
      $error = $assets->orderPlacement($data['assets'],1,$order_id);
      if($error != null){
        return response()->json([
          "Code" => 403,
          "is_valid_order" => 0,
          "Message_en" => $error,
          "Message_ar" => $error
        ]);
      }
    
    }

    if(isset($data['skips']) && $data['skips'] != ""){
    
      $assets = new OrderServiceRequest();
      $error = $assets->skipOrder($data['skips'],$order_id,$data['start_date']=null);

      if($error != null){
        return response()->json([
          "Code" => 403,
          "is_valid_order" => 0,
          "Message_en" => $error,
          "Message_ar" => $error
        ]);
      }
    
    }

    if(isset($data['equipment']) && $data['equipment'] != ""){

      $equipment = new OrderServiceRequest();
      $error = $equipment->orderPlacement($data['equipment'],1,$order_id);
      if($error != null){
        return response()->json([
          "Code" => 403,
          "is_valid_order" => 0,
          "Message_en" => $error,
          "Message_ar" => $error
        ]);
      }

  }

  if(isset($data['labor']) && !empty($data['labor'])){
    foreach($data['labor'] as $item){
    $id = ServiceCategory::where('title', 'like', '%' . $item['label'] . '%')->value('service_category_id');
    OrderServiceRequest::updateOrCreate(['order_id' => $order_id,'service_category_id' => $id],['value' => $item['value'] ,'status' => 1]);
    }
    }
  if (!empty($getOrder)) {
  if (isset($getOrder['order_id']['code']) && !empty($getOrder['order_id']['code'] == 403)) {
  return response()->json([
    "Code" => 403,
    "is_valid_order" => 0,
    "Message_en" => "Insufficient Credit, Please Contact Your Account Manager",
    "Message_ar" => "برجاءالتواصلمعمديرحسابكفىا"
  ]);
  }

  if (isset($getOrder['error']) && !empty($getOrder['error'])) {
  return response()->json([
    "Code" => 403,
    "is_valid_order" => 0,
    "Message_en" => $getOrder['error'],
    "Message_ar" => $getOrder['error']
  ]);
  }

  if (isset($getOrder['order_id']) && !empty($getOrder['order_id'])) {

  /*if(isset($data['payment_response']) && !empty($data['payment_response'])){ // Payment Response
  $getPaymentResponse = $this->paymentResponse($data['payment_response'],$getOrder['order_id'],$getOrder['grand_total'],$data['payment_type']);
  }*/

  if (!empty($reqResId)) {
  $mobile->updateReqRespone($reqResId);
  }

  /* Get order Code - Start */
  $orderCode = $orderModal->getOrderCodeByOrderId($getOrder['order_id']);
  /* Get order Code - Start */



  // /* Sync order in ERP - START */
  // if($customer->account_type_id != 0){
  // $erp_modal = new ErpOrderController();
  // $erp_modal->AddOrderInErp($getOrder['order_id']);
  // }
  // /* Sync order in ERP - END */

  try{
    OrderHistory::maintainOrderHistory($history_data,$order_id);
    if((isset($history_data['labor']) && !empty($history_data['labor'])) || (isset($history_data['equipment']) && !empty($history_data['equipment']))){
      OrderServiceRequestHistory::maintainServiceRequestHistory($history_data,$order_id);

    }

    if(isset($history_data['selected_material']) && !empty($history_data['selected_material'])){
      OrderMaterialHistory::maintainMaterialHistory($history_data,$order_id);
    }
  }catch (\Exception $ex) {
    $response = [
        "code" => 500,
        "data" => [
            "error" => $ex->getMessage()
        ],
        'message' => 'Error in updating place order history.'
    ];
    return response()->json($response);
  }

  $customer_name = Customer::where('customer_id',$data['user_id'])->value('name');

  // PushNotification::createOrderNotification($data['user_id'], $orderCode);
  // PushNotification::createOrderNotificationAdmin($data['user_id'], $orderCode, $customer_name);

  return response()->json([
  "Code" => 200,
  "data" => ["id" => $orderCode,
  "order_id" => $order_id,
  "result" => 'success',
  "address_id" => $data['addressData']['ord_address_id'],
  ],
  "message" => "Order has been placed successfully."
  ]);
  }
  }


  }

  public function customersListing(Request $request){
    
    $data =  $request->all();
   
   $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

    $customers = Customer::whereStatus(1);
    
    if(isset($data['customer']) && $data['customer'] != ""){
      $customers->where('customer_id',$data['customer']);
    }
    if(isset($data['from']) && $data['from'] != ""){
      $customers->whereDate('created_at','>=',$data['from']);
    }
    if(isset($data['to']) && $data['to'] != ""){
      $customers->whereDate('created_at','<=',$data['to']);
    }
    $customers = $customers->orderBy('updated_at','DESC')->paginate($data['perPage'])->toArray();
    
    return response()->json([
      "Code" => 200,
      "data" => ["customers" => $customers],
      "Message" => __("List of Customers fetched Successfully.")
      ]);
  }

  public function supervisorOrderUpdate(Request $request){
    
    $data = $request->all();

    
    $data['json_data'] = preg_replace("/\r(?!\n)/", '', $data['json_data']);
    $data['json_data'] = str_replace('"{','{',$data['json_data']);
    $data['json_data'] = str_replace('}"','}',$data['json_data']);
    $data['json_data'] =  json_decode($data['json_data'], true);
    $data = $data['json_data'];

    $validator = Validator::make([    
      'order_id' => $data['order_id']
    ],[
      'order_id' => 'required|integer|exists:orders,order_id'
    ]);

    if ($validator-> fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
    }

    $order = $order_data = Order::where('order_id',$data['order_id'])->first();
   

    try{
    $order->update([
      'estimated_end_date' => isset($data['general']['estimated_end_date']) ? $data['general']['estimated_end_date'] : null,
      'required_start_date' => isset($data['general']['required_start_date']) ? $data['general']['required_start_date'] : null,
      'contract_work_permit' => isset($data['general']['access']) ? $data['general']['access'] : null,
      'prev_order_status_id' => $order_data->order_status_id != 16 ?? $order_data->order_status_id,
      'order_status_id' => 16
    ]);
    $service_requests = [];
    
    #if weighing bridge is required
    if(isset($data['load_and_transport']['model']) && !empty($data['load_and_transport']['model'])){
      $sc_id = ServiceCategory::where('model',$data['load_and_transport']['model'])->value('service_category_id');
      OrderServiceRequest::updateOrCreate(['order_id' => $data['order_id'],'service_category_id' => $sc_id],['value' => 0,'status' => 1]);
      $service_requests[] = $sc_id;
      
    }
    if(isset($data['general']['drop_of_site_location']) && !empty($data['general']['drop_of_site_location']) && $data['general']['drop_of_site_location'] != null){
      $order->update(['aqg_dropoff_loc_id' => $data['general']['drop_of_site_location']]);
    }
    if(isset($data['man_power']) && !empty($data['man_power'])){
    foreach($data['man_power'] as $item){
    $id = ServiceCategory::where('title', 'like', '%' . $item['label'] . '%')->value('service_category_id');
    OrderServiceRequest::updateOrCreate(['order_id' => $data['order_id'],'service_category_id' => $id],['value' => $item['value'] ,'status' => 1]);
    $service_requests[] = $id;

    }
    }

    Planning::updateOrCreate(['order_id' => $data['order_id']],
    ['health_safety' => $data['general']['health_safety'] , 'safety_orientation' => $data['general']['safety_orientation'] ,
    'working_days' => $data['general']['working_days'] , 'working_hours' => $data['general']['working_hours'], 'trips' => $data['load_and_transport']['trip'],
    'trucks' => $data['load_and_transport']['truck'] ]);
    $order_material = [];
    
    if(isset($data['types_of_material_to_be_collected']) && !empty($data['types_of_material_to_be_collected'])){
  
    foreach($data['types_of_material_to_be_collected'] as $item){
      $material_id = Material::where('name', 'like', '%' . $item['label'] . '%')->value('material_id');
      if($material_id == null){
        $material_id = Material::insertGetId(['name' => $item['label']]);
      }
    
      $order_material[] = $material_id;
      OrderMaterial::updateOrCreate(['order_id' => $data['order_id'],'material_id' => $material_id],
                                    ['weight' => $item['weight'],'value' => $item['value'],'unit' => $item['unit'] , 'remarks' => $item['remarks'] ,'length' => isset($item['lengths']) ? $item['lengths'] : 0,'status' => 1]);
    }
  }
  if(isset($data['documentation']) && !empty($data['documentation'])){
    $index = 0;
    $planning_doc = array();
    $doc = new PlanningDocumentation();

    $stored_docs = [];
    foreach($data['documentation'] as $item){
      if ($request->input('attachment')){
        array_push($stored_docs,$request->input('attachment'));
      }
      if ($request->hasfile('attachment')) {
          $files = $request->file('attachment');
          if(isset($files[$index])){
            $attachment = $files[$index];
            $item['attachment'] = $doc->upload($attachment,'document',$data['order_id']);
            array_push($planning_doc, [
              'attachment' => $item['attachment'],
              'description' => $item['description'],
              'remarks' => $item['remarks'] ,
              'status' => 1 ,
              'order_id' => $data['order_id']
            ]);
          }
      }    
      $index++;
    }
    if(count($stored_docs) > 0){
      $stored_docs = array_unique($stored_docs, SORT_REGULAR);
      PlanningDocumentation::where('order_id',$data['order_id'])->whereNotIn('attachment',$stored_docs[0])->delete();  
    }
    $doc->insert($planning_doc);
  }
  
  if(isset($data['mobilization']) && !empty($data['mobilization'])){
    foreach($data['mobilization'] as $item){
      OrderServiceRequest::updateOrCreate([
        'order_id' => $data['order_id'], 
        'service_category_id' => $item['sub_category']['value']],
      ['is_client_approval_required' => $item['client_approval'],
      'start_date' => $item['date_requested'],
      'days_count' => $item['days'],
      'is_govt_approval_required' => $item['gov_approval'],
        'quantity' => $item['qty'], 
        'remarks' => $item['remarks'],
        'status' => 1]);
      $service_requests[] = $item['sub_category']['value'];

    }
  }

    OrderServiceRequest::where('order_id',$data['order_id'])->whereNotIn('service_category_id',$service_requests)->update(['status' => 9]);
    OrderMaterial::where('order_id',$data['order_id'])->whereNotIn('material_id',$order_material)->update(['status' => 9]);

    // $notify = \App\Model\Template::sendOrderStatusNotifications($order->order_id);
  
    $user = (Auth::user());
    $user_id = ($user->user_id);

  }catch(\Exception $ex) {
    $response = [
        "code" => 500,
        "data" => [
            "error" => $ex->getMessage()
        ],
        'message' => 'Error in approving order.'
    ];
    return response()->json($response);
  }


  $data_history = [ "order_id" => $data['order_id'],
    "user_id" => $user_id,
    "customer_id" => isset($data['customer_id']) ? $data['customer_id'] : null,
    "contract_work_permit" => isset($data['general']['access']) ? $data['general']['access'] : null,
    "comments" => isset($data['general']['comments']) ? $data['general']['comments'] : null,
    "start_date" => isset($data['general']['required_start_date']) ? $data['general']['required_start_date'] : null,
    "end_date" => isset($data['general']['estimated_end_date']) ? $data['general']['estimated_end_date'] : null   
  ];
    
  
    if((isset($data['labor']) && !empty($data['labor'])) || (isset($data['equipment']) && !empty($data['equipment']))){
      OrderServiceRequestHistory::maintainServiceRequestHistory($data,$data['order_id']); 
    }
    
    if(isset($data['selected_material']) && !empty($data['selected_material'])){
      OrderMaterialHistory::maintainMaterialHistory($data,$data['order_id']);
    }

    $orderlogsdata[] = [

      'order_id' => $data['order_id'],
      'order_status_id' => 16,
      'source_id' => 12,
      'user_id' => $order['customer_id'],
      'created_at' =>  date('Y-m-d H:i:s'),
      'updated_at' =>  date('Y-m-d H:i:s')
    
    
    ];
    
    OrderLogs::insert($orderlogsdata);


  return response()->json([
  "Code" => 200,
  "data" => "",
  "Message" => __("Order planning updated Successfully.")
  ]);
  }

  public function orderUpdate(Request $request){

    $data =  json_decode($request->getContent(),true);

    $validator = Validator::make($request->all(), [
      'order_id' => 'required|integer|exists:orders,order_id',
      'estimated_weight' => 'nullable|numeric',
      'pickup_site_location' => 'nullable|integer|exists:addresses,address_id',
      'dropoff_site_location' => 'nullable|integer|exists:addresses,address_id',
      'no_of_vehicles' => 'nullable|integer',
      'equipment.*.service_category_id' => 'nullable|integer|exists:service_category,service_category_id',
      'labor.*.service_category_id' => 'nullable|integer|exists:service_category,service_category_id',
      'material_types.*.weight' => 'nullable|numeric',
      'material_types.*.unit' => 'required_with:material_types.*.weight|nullable|numeric|exists:units,id',
      'required_start_date' => 'nullable|date',
      'estimated_end_date' => 'nullable|date',
  ]);
  if ($validator->fails()) {
    return responseValidationError('Fields Validation Failed.', $validator->errors());
  }
    
    #Check if order exists
    $order = Order::where('order_id',$data['order_id'])->first();
    if(is_object($order) && count(get_object_vars($order)) != 0){

      $service_requests = [];
      $service_requests = OrderServiceRequest::where('order_id' , $data['order_id'])->pluck('service_category_id'); #fetching lists of 
      $decoded = json_decode(json_encode($service_requests), true);
      $order_material = [];
      $order_material = OrderMaterial::where('order_id' , $data['order_id'])->pluck('material_id'); #fetching list of materials against this order
      $material_list = json_decode(json_encode($order_material), true);

      if(isset($data['estimated_weight']) && !empty($data['estimated_weight'])){
        $order->update(['net_weight' => $data['estimated_weight']]);
      }
      if((isset($data['aqg_loc_id']) && $data['aqg_loc_id'] != "" && $data['aqg_loc_id'] != 0) 
      && (isset($data['dropoff_site_location']) && $data['dropoff_site_location'] != "" && $data['dropoff_site_location'] != 0)){
        if($data['dropoff_site_location'] > 0){
          $data['aqg_loc_id'] = 0;
        }
        // if($data['aqg_loc_id'] > 0){
        //   $data['dropoff_site_location'] = 0;
        // }
      }

      if(isset($data['aqg_loc_id']) && !empty($data['aqg_loc_id'])){
        $order->update(['aqg_dropoff_loc_id' => $data['aqg_loc_id']]);
      }
      if(isset($data['dropoff_site_location']) && !empty($data['dropoff_site_location'])){
        $order->update(['customer_dropoff_loc_id' => $data['dropoff_site_location']]);
      }
      if(isset($data['no_of_vehicles']) && !empty($data['no_of_vehicles'])){
        $order->update(['required_vehicles' => $data['no_of_vehicles']]);
      }
      if(isset($data['contract_work_permit']) && $data['contract_work_permit'] != ""){
        $order->update(['contract_work_permit' => $data['contract_work_permit']]);
      }



    if(isset($data['material_types']) && !empty($data['material_types'])){
    $order_material = [];
    $order_material = OrderMaterial::where('order_id' , $data['order_id'])->pluck('material_id');
    $material_list = json_decode(json_encode($order_material), true);
    
    foreach($data['material_types'] as $item){
      $material_id = Material::where('name', 'like', '%' . $item['label'] . '%')->value('material_id');
      if($material_id == null){
        $material_id = Material::insertGetId(['name' => $item['label']]);
      }
      if (($key = array_search($material_id, $material_list)) !== false) {
        unset($material_list[$key]);
      }
      OrderMaterial::updateOrCreate(['order_id' => $data['order_id'],'material_id' => $material_id],
                                    ['weight' => $item['weight'],'value' => $item['value'],'unit' => $item['unit'] , 'remarks' => $item['remarks'] ,'length' => isset($item['lengths']) ? $item['lengths'] : 0,'status' => 1]);
    }
  }


  if(isset($data['labor']) && !empty($data['labor'])){
    foreach($data['labor'] as $item){
    // $id = ServiceCategory::where('title', 'like', '%' . $item['label'] . '%')->value('service_category_id');
    OrderServiceRequest::updateOrCreate(['order_id' => $data['order_id'],'service_category_id' => $item['service_category_id']] ,['status' => 1]);
      if (($key = array_search($item['service_category_id'], $decoded)) !== false) {
        unset($decoded[$key]);
      }
    }
    }


  if(isset($data['equipment']) && !empty($data['equipment'])){
    foreach($data['equipment'] as $item){
      OrderServiceRequest::updateOrCreate(['order_id' => $data['order_id'], 'service_category_id' => $item['service_category_id']],
                                        ['quantity' => $item['quantity'],'is_client_approval_required' => $item['is_client_approval_required'], 'start_date' => $item['start_date'],
                                        'days_count' => $item['days_count'], 'is_govt_approval_required' => $item['is_govt_approval_required'], 'remarks' => isset($item['remarks']) ? $item['remarks'] : null,'status' => 1]);
      if (($key = array_search($item['service_category_id'], $decoded)) !== false) {
        unset($decoded[$key]);
      }
    }
  }

  if(isset($data['required_start_date']) && !empty($data['required_start_date'])){
    $order->update(['required_start_date' => $data['required_start_date']]);
  }
  if(isset($data['estimated_end_date']) && !empty($data['estimated_end_date'])){
    $order->update(['estimated_end_date' => $data['estimated_end_date']]);
  }

    if(isset($data['user_id']) && !empty($data['user_id']) && is_numeric($data['user_id'])){
      if(isset($data['estimated_weight']) && !empty($data['estimated_weight']) && is_numeric($data['estimated_weight'])){
        Order::where('order_id',$data['order_id'])->update(['net_weight' => $data['estimated_weight']]);
        $order_history = OrderHistory::insert(['user_id' => $data['user_id'], 'weight' => $data['estimated_weight'], 'order_id' => $data['order_id']]);
      }
    }else{
      return response()->json([
        "Code" => 403,
        "Message" => __("Missing user ID and customer ID.")
      ]);
    }
    

    if(isset($data['estimated_end_date']) && !empty($data['estimated_end_date'])){
      Order::where('order_id',$data['order_id'])->update(['estimated_end_date' => $data['estimated_end_date']]);
    }
    
    OrderServiceRequest::where('order_id',$data['order_id'])->whereIn('service_category_id',$decoded)->update(['status' => 9]);
    OrderMaterial::where('order_id',$data['order_id'])->whereIn('material_id',$material_list)->update(['status' => 9]);
    $order = Order::where('order_id',$data['order_id'])->first();
    $order->update(['prev_order_status_id' => $order['order_status_id'],'order_status_id' => 15]);

    $orderlogsdata[] = [

      'order_id' => $data['order_id'],
      'order_status_id' => 15,
      'source_id' => 12,
      'user_id' => $data['user_id'],
      'created_at' =>  date('Y-m-d H:i:s'),
      'updated_at' =>  date('Y-m-d H:i:s')
    
    
    ];
    
    OrderLogs::insert($orderlogsdata);

    $user = (Auth::user());
    $user_id = ($user->user_id);
    $data_history = [ "order_id" => $data['order_id'],
    "user_id" => $user_id,
    "customer_id" => isset($data['customer_id']) ? $data['customer_id'] : null,
    "pickup_address_id" => isset($data['pickup_site_location']) ? $data['pickup_site_location'] : null,
    "customer_dropoff_loc_id" => isset($data['dropoff_site_location']) ? $data['dropoff_site_location'] : null,
    "contract_work_permit" => isset($data['general']['access']) ? $data['general']['access'] : null,
    "required_vehicles" => isset($data['no_of_vehicles']) ? $data['no_of_vehicles'] : null,
    "comments" => isset($data['general']['comments']) ? $data['general']['comments'] : null,
    "start_date" => isset($data['general']['required_start_date']) ? $data['general']['required_start_date'] : null,
    "end_date" => isset($data['general']['estimated_end_date']) ? $data['general']['estimated_end_date'] : null   
  ];

    OrderHistory::maintainOrderHistory($data_history,$data['order_id']);
    if((isset($data['labor']) && !empty($data['labor'])) || (isset($data['equipment']) && !empty($data['equipment']))){
      OrderServiceRequestHistory::maintainServiceRequestHistory($data,$data['order_id']);
    }
    
    if(isset($data['material_types']) && !empty($data['material_types'])){
      $data['selected_material'] = $data['material_types'];
      OrderMaterialHistory::maintainMaterialHistory($data,$data['order_id']);
    }

    // $notify = \App\Model\Template::sendOrderStatusNotifications($order->order_id);
  
    return response()->json([
      "Code" => 200,
      "data" => "",
      "Message" => __("Order has been updated Successfully.")
    ]);
    }
    else{
      return response()->json([
        "Code" => 403,
        "data" => "",
        "Message" => __("Order Not Found.")
      ]);
    }
  }

  public function getOrders(Request $request)
  {
    $data =  json_decode($request->getContent(),true);

    if($data == ""){
      return response()->json([
        "Code" => 403,
        "data" => "",
        "Message" => "Invalid json."
      ]);
    }

    if(!isset($data['user_id']) || empty($data['user_id'])){
      return response()->json([
        "Code" => 403,
        "data" => "",
        "Message" => "Missing Input."
      ]);
    }
    $user = auth()->guard('oms')->user();
    if($user->customer_id != $data['user_id']){
      return response()->json([
        "Code" => 403,
        "data" => "",
        "Message" => __("Unauthorized User.")
      ]);
    }
    $order = new GetOrder();
    $mobile = new Mobile();
    $data['user_id'] = trim($mobile->clean_sqlwords($data['user_id']));

    $detail =  $order->getCustomerOrders($data);

    $refund_to_wallet = Option::getValueByKey('REFUND_TO_WALLET');
    $refund_to_bank = Option::getValueByKey('REFUND_TO_BANK');
    $manual_refund = Option::getValueByKey('MANUAL_REFUND');

    if(count($detail) >= 1)
    {
      $detail = $detail->toArray();
      $order_detail = array();
      // return $detail;
      for($i = 0; $i < count($detail) ; $i++){
        if($detail[$i]['order_status_id'] == 14 || $detail[$i]['order_status_id'] == 13){
          $detail[$i]['order_status_id'] = 2;
          $detail[$i]['order_status']['order_status_title'] = '{"en":"Confirmed","ar":"تم تأكيد"}';
        }
        $orderStatusTitle = isset($detail[$i]['order_status']['order_status_title']) ? json_decode($detail[$i]['order_status']['order_status_title']) : null;
        $paymentType = json_decode($detail[$i]['payment_method_info']['option_name']);
       
        $cancel = 0;
        if($detail[$i]['order_status_id'] == 2 || $detail[$i]['order_status_id'] == 5 || $detail[$i]['order_status_id'] == 3 || $detail[$i]['order_status_id'] == 13 || $detail[$i]['order_status_id'] == 14){
          if($detail[$i]['payment_method'] == 'CASH_ON_DELIVERY' || $detail[$i]['payment_method'] == 'CUSTOMER_CREDIT' || $detail[$i]['payment_method'] == 'CARD_ON_DELIVERY'){
            $cancel = 1;
          }
        }else{
          $Cancel = 0;
        }
        $detail[$i]['created_at'] = str_replace(['T'], ' ', $detail[$i]['created_at']);
        $detail[$i]['created_at'] = str_replace(['.000000Z'], '', $detail[$i]['created_at']);
        
        array_push($order_detail, array(
          "id" => (string)$detail[$i]['order_id'],
          "ord_id" => (string)$detail[$i]['order_number'],
          "ord_user_id" => (string)$detail[$i]['customer_id'],
          "order_number" => (string)$detail[$i]['order_number'],
          "ord_note" => "",
          "ord_delivery_charge" => "0",
          "current_status" => (string)$detail[$i]['order_status_id'],
          "invoice_number" => "",
          "ord_date" => $detail[$i]['created_at'],
          "iscancelable" => (string) $cancel,
          "iseditable" => "0",
          "isreorderable" => 1,
          "order_status_en" => !isset($orderStatusTitle->en) ? "" : $orderStatusTitle->en ,
          "order_status_ar" => !isset($orderStatusTitle->ar) ? "" : $orderStatusTitle->ar ,
          
        ));
      }
      return response()->json([
        "Code" => 200,
        "data" => $order_detail,
        "Message" => __("Order Detail.")
      ]);
    }
    else
    {
      return response()->json([
        "Code" => 403,
        "data" => "",
        "Message" => "Orders not found."
      ]);
    }
  }

  public function getOrderDetail(Request $request)
  {
   
    $data =  json_decode($request->getContent(),true);
    $validator = Validator::make($request->all(), [
      'ord_id' => 'required|integer|exists:orders,order_id',
    ]);
    if ($validator->fails()) {
      return responseValidationError('Fields Validation Failed.', $validator->errors());
    }

    if($data != "")
    {
      if(isset($data['ord_id']))
      {
        if(isset($data['admin']) && $data['admin'] == 1){
          
        }else{
          if(!isset($data['client_id'])){
            return response()->json([
              "Code" => 403,
              "data" => "",
              "Message" => "Missing Input."
            ]);
          }
          $user = auth()->guard('oms')->user();
          if($user->customer_id != $data['client_id']){
            return response()->json([
              "Code" => 403,
              "data" => "",
              "Message" => "Unauthorized User."
            ]);
          }
        }
      

        $order = Order::with('customer:customer_id,name','customer_warehouse','aqg','pickup',
                'order_status:order_status_id,order_status_title,key','category:category_id,category_name,key',
                'weight_unit:id,unit','shipping_address:address_id,address_title,address,latitude,longitude',
                'site_location:address_id,address_title,address,latitude,longitude','lot')
                ->where('order_id',$data['ord_id'])->get();
        
        $created_at = strtotime( $order[0]['created_at'] );  
        $required_start_date = strtotime( $order[0]['required_start_date'] );  
        $estimated_end_date = strtotime( $order[0]['estimated_end_date'] ); 

        //Ayesha 04-08-2022 Removing am/pm format from dates 
        $order[0]['created_at'] = date('Y-m-d H:i:s', $created_at ); 
        $order[0]['required_start_date'] = date('Y-m-d H:i:s', $required_start_date );
        $order[0]['estimated_end_date'] = date('Y-m-d H:i:s', $estimated_end_date );

        if(isset($order[0]['unit'])){
          $order[0]['unit'] = $order[0]['weight_unit']['unit'];
        }
        if(!$order->isEmpty()){
          $order = $order[0];
          $order['can_complete'] = true;
          $equipment = OrderServiceRequest::listOfEquipment($data['ord_id']);
          $tools = OrderServiceRequest::listOfTools($data['ord_id']);
          $order['labors'] = OrderServiceRequest::listOfLabors($data['ord_id']);
          $order['mobilization'] = array_merge($equipment,$tools);    
          $order['assets'] = OrderServiceRequest::listOfAssets($data['ord_id']); 
          $assets = $order['assets'];
          

          if($order['category_id'] == 4){
            $assets_list_arr = [];
            foreach($assets as $a_item){
              foreach($a_item['service_category']['items'] as $_asset){
                if($_asset['asset_id'] == $a_item['skip']['asset_id']){
                  $assets_list['title'] = $_asset['title'];
                }
              }
              $assets_list['skip_id'] = $a_item['skip_id'];
              $assets_list['service_category_title'] = $a_item['service_category']['title'];
              $assets_list['replacement'] = $a_item['replace'] == 1 ? 1 : 0;
              $assets_list['skip_level'] = isset($a_item['skip']) && isset($a_item['skip']['current_skip_level']) ? $a_item['skip']['current_skip_level']['skip_level'] : 0;
              $assets_list['color'] = isset($a_item['skip']) && isset($a_item['skip']['current_skip_level']) ? $a_item['skip']['current_skip_level']['color'] : 0;
              array_push($assets_list_arr,$assets_list);
            }
            $order['assets'] = $assets_list_arr;
            
          }
        
          $planning = Planning::where('order_id',$data['ord_id'])->select('health_safety','safety_orientation','working_days','working_hours','trucks','trips')->get()->toArray();
          $customer_id = $order['customer_id'];
          $material = OrderMaterial::with(['material:material_id,name','material_unit:id,unit','material.skips' => function($q) use($customer_id) {
            
            $q->where('customer_id', '=', $customer_id)->where('status',1); 
          },'material.skips.asset_inventory:asset_id,title'])->where('order_id',$data['ord_id'])->whereStatus(1)->get();
          
          foreach($material as $item){
            $material_data[] = [
            "value" => isset($item['value']) ? $item['value'] : null,
            "label" => isset($item['material']['name']) ? $item['material']['name'] : null,
            "remarks" => isset($item['remarks']) ? $item['remarks'] : null,
            "weight" => isset($item['weight']) ? $item['weight'] : null,
            "unit" => isset($item['material_unit']['unit']) ? $item['material_unit']['unit'] : null,
            "material_id" => isset($item['material']['material_id']) ? $item['material']['material_id'] : null ,
            "skip_title" => isset($item['material']['skips']) && isset($item['material']['skips']['asset_inventory']) ? $item['material']['skips']['asset_inventory']['title'] : null
          ];
          }
          foreach($order['labors'] as $item){
            $labor_data[] = [
              "value" => isset($item['value']) ? $item['value'] : null ,
              "label" => isset($item['service_category']['title']) ? $item['service_category']['title'] : null,
               "name" => "", "nationality" => ""];
          }
          $order['materials'] = isset($material_data) ? $material_data : null;
          $order['man_power'] = isset($labor_data) ? $labor_data : null;
          $order['no_of_trips'] = isset($planning[0]['trips']) ? $planning[0]['trips'] : null;
          $order['trucks'] = isset($planning[0]['trucks']) ? $planning[0]['trucks'] : null;
          $selected_statuses = ['Placed', 'Accepted','Execution'];

          $actual_trips = DeliveryTrip::where('order_id',$data['ord_id'])->count();
          $actual_weight = PickupMaterial::where('order_id',$data['ord_id'])->sum('weight');

          $order['actual_trips_no'] = $actual_trips;
          $order['actual_weight'] = $actual_weight;
          $placed = OrderLog::where('order_status_id',5)->where('order_id',$data['ord_id'])->value('created_at');
          $waiting_for_acceptance = OrderLog::where('order_status_id',15)->where('order_id',$data['ord_id'])->value('created_at');
          $accepted = OrderLog::where('order_status_id',16)->where('order_id',$data['ord_id'])->value('created_at');
          $execution = OrderLog::where('order_status_id',17)->where('order_id',$data['ord_id'])->value('created_at');
          $completed = OrderLog::where('order_id',$data['ord_id'])->whereIn('order_status_id',[4,6])->value('created_at');
          $kpi = array();
    
          isset($placed) ? array_push($kpi, array("description" => date_format(date_create($placed), "Y-m-d H:i:s"))) : array_push($kpi, null);
          isset($waiting_for_acceptance) ? array_push($kpi, array("description" => date_format(date_create($waiting_for_acceptance), "Y-m-d H:i:s"))) : array_push($kpi, null);
          isset($accepted) ? array_push($kpi, array("description" => date_format(date_create($accepted), "Y-m-d H:i:s"))) : array_push($kpi, null);
          isset($execution) ? array_push($kpi, array("description" => date_format(date_create($execution), "Y-m-d H:i:s"))) : array_push($kpi, null);
          isset($completed) ? array_push($kpi, array("description" => date_format(date_create($completed), "Y-m-d H:i:s"))) : array_push($kpi, null);
          $order['kpi'] =   $kpi;      
          // $order['kpi'] = 

          if($order->category_id != 3 || $order->category_id != 4){
            array_push($selected_statuses,'Waiting_For_Acceptance');
          }

          if (isset($order->cancel_reason_id) && $order->cancel_reason_id > 0 && isset($status_cancelled)) {
            //order has been cancelled so add, 'CANCELLED' order status
            array_push($selected_statuses,'Cancelled');
          } else {
            array_push($selected_statuses,'Completed');
          }
          
          //Update number of trucks if order is in 'Completed' state
          if(isset($order['order_status_id']) && $order['order_status_id'] == 4){
            $trucks = DB::table('delivery_trips')->where('order_id',$order['order_id'])->distinct('vehicle_id')->count('vehicle_id');
            $order['trucks'] = $trucks;
          }


          $order_statuses = OrderStatus::select('order_status_id','order_status_title','key');
          foreach($selected_statuses as $word){
            $order_statuses->orWhere('key', 'LIKE', '%'.$word.'%');
          }
          $order_statuses = $order_statuses->orderBy('sequence','ASC')->get();

          $order['order_statuses'] = $order_statuses;

          try {
            $delivery_trips = DeliveryTrip::with('trip_status:trip_status_id,trip_status_title,key',
            'vehicle:vehicle_id,vehicle_plate_number,current_latitude,current_longitude,driver_id','vehicle.driver:user_id,first_name,last_name',
            'pickup_material.material_unit','pickup_material.material','dropoff_material.dropoff_unit','constraints:key,trip_id,location_id',
            'constraints.locations:location_id,delay,location_name,latitude,longitude')
            ->withSum('pickup_material','weight')
            ->orderBy('created_at','DESC')
            ->orderBy('delivery_trip_id','DESC')
            ->where('order_id',$order->order_id)
            ->get()->toArray();

            
            foreach($delivery_trips as &$del_trips){

              $del_trips['dropoff_location'] = ["latitude" => $del_trips['dropoff_latitude'] , "longitude" => $del_trips['dropoff_longitude']];

            
             
               // If Order is pickup order and on oms hit , 
              if(Auth::guard('oms')->check() && $order['category']['key'] == "PICKUP"){

                $lat_lng = ["latitude" => null , "longitude" => null];
                $del_trips['dropoff_location'] = $lat_lng;

                $diff_in_minutes = \Carbon\Carbon::parse($del_trips['trip_startime'])->diffInMinutes(\Carbon\Carbon::parse( $del_trips['load']));
                $hours = floor($diff_in_minutes / 60);
                $minutes = $diff_in_minutes % 60;
               
                $calculated_time = "".str_pad($hours, 2, '0', STR_PAD_LEFT). ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
                $totalservicetime = $del_trips['actual_pstime'];
                $servicetime = $del_trips['pickup_service_time'];
                $calculated_distance = $del_trips['start_latitude'] == "" ||  $del_trips['start_longitude'] == "" ? "" : \App\Model\Delivery::distance($del_trips['start_latitude'],$del_trips['start_longitude'],$del_trips['pickup_latitude'],$del_trips['pickup_longitude']);
                $calculated_distance = is_numeric($calculated_distance) ? $calculated_distance." KM" : "";
                $estimated_time=date_format(date_create($del_trips['pickup_time']), "H:i:s");
  
                $estimated_dist=($del_trips['pickup_distance'] == NULL) ? "" : $del_trips['pickup_distance'].' KM';

              }else{

                if($del_trips['trip_status']['key'] != "CLOSED" && $del_trips['trip_status']['key'] != "ASSIGNED" && $del_trips['trip_status']['key'] != "CANCEL")
                {
                 
                  $mytime = Carbon::now();
                    $starttime = ($del_trips['trip_startime'] == NULL) ? "" : date_format(date_create($del_trips['trip_startime']), "Y-m-d H:i:s");
                    $starttime = Carbon::createFromFormat('Y-m-d H:i:s', $starttime);
                    $diff = $starttime->diffInMinutes($mytime);
                    // $calculated_time = gmdate("H:i:s", ($diff * 60));
                    $hours = floor($diff / 60);
                    $minutes = $diff % 60;
                    
                    $calculated_time = "".str_pad($hours, 2, '0', STR_PAD_LEFT). ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
                    
                    $getLoc=DB::table('vehicle_locations')->where('delivery_trip_id',$del_trips['delivery_trip_id'])
                    ->pluck('vehLoc')->toArray();
                    
                    if(is_array($getLoc) && count($getLoc) > 0)
                    {
                        $all_data = json_decode($getLoc[0]);
                        $index=count($all_data);
                        $index=$index-1;
                        $all_data= $all_data[$index];
                
                        $lat2=$all_data->lat;
                        $lng2=$all_data->lng;
                        $lat=($del_trips['start_latitude'] == NULL) ? "" : $del_trips['start_latitude'];
                        $lng=($del_trips['start_longitude'] == NULL) ? "" : $del_trips['start_longitude'];
                    
                        if($lat == NULL||$lng == NULL || $lat2 == NULL || $lng2 == NULL )
                        {
                            $calculated_distance= 0;
                        }
                        else {
                            $calculated_distance= _getDistance($lat,$lng,$lat2,$lng2);

                        }
                    }
                    else {
                        $calculated_distance=0;

                    }

                    $calculated_distance = is_numeric($calculated_distance) ? $calculated_distance." KM" : "";
                }else{
                  $calculated_time = $del_trips['actual_time'] == NULL ? "" : $del_trips['actual_time'];
                  $calculated_distance = ($del_trips['actual_distance'] == NULL && !is_numeric($del_trips['actual_distance'])) ? "" : $del_trips['actual_distance'];

                }
                $estimated_time=($del_trips['total_time'] == NULL) ? "" : $del_trips['total_time'];
                $estimated_dist= ($del_trips['total_distance'] == NULL) ? "" : $del_trips['total_distance'].' KM';

                //Calculate Actual Service Time
                if($del_trips['actual_dstime'] != NULL)
                {
                    $secs = strtotime($del_trips['actual_dstime'])-strtotime("00:00:00");
                    $totalservicetime = date("H:i:s",strtotime($del_trips['actual_pstime'])+$secs);
                }
                else{
                    $totalservicetime=NULL;

                }

                //Calculate Planned Service Time
                $time = isset($del_trips['pickup_service_time']) && $del_trips['pickup_service_time'] != NULL ? strtotime($del_trips['pickup_service_time'])-strtotime("00:00:00") : null;
                $servicetime = isset($del_trips['dropoff_service_time']) && $del_trips['dropoff_service_time'] != NULL ? date("H:i:s",strtotime($del_trips['dropoff_service_time'])+$time) : $del_trips['pickup_service_time'];
                
              }

              
              $del_trips['calculated_dist'] = $calculated_distance;
              $del_trips['estimated_dist'] = $estimated_dist;
              $del_trips['calculated_time'] = $calculated_time;
              $del_trips['estimated_time'] = $estimated_time;
              $del_trips['total_service_time'] = ($totalservicetime == NULL )? "" : $totalservicetime;
              $del_trips['planned_total_service_time'] = ($servicetime == NULL )? "" : $servicetime;

              if(isset($del_trips['dropoff_material']) && $del_trips['dropoff_material'] !=null){
                foreach($del_trips['dropoff_material'] as &$dropoff){

                  $dropoff['unit'] = $dropoff['dropoff_unit']['unit'];
    
                }
              }
              
              
            }
            

          }catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in fetching Order detail delivery trips.'
            ];
            return response()->json($response);
          }
        
          //Calculating sum of material 
          try {

            $material_sum = \App\Model\PickupMaterial::
            join('delivery_trips','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')
            ->leftJoin('dropoff_materials','dropoff_materials.trip_id','=','delivery_trips.delivery_trip_id')
            ->select('pickup_materials.material_id', DB::raw('sum(pickup_materials.weight) as sum'),DB::raw('sum(dropoff_materials.weight) as dropoff_sum'),'pickup_materials.unit')
            ->groupBy('material_id')
            ->where('delivery_trips.order_id',$order->order_id)
            ->get()->toArray();

          }catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in calculating sum of material.'
            ];
            return response()->json($response);
          }

          $sum = array();
          foreach($material_sum as &$mat){

            $new = $mat['material_id'];
            $mat[$new] = $mat['sum'];
            $mat[$new] = [$mat['sum'],$mat['dropoff_sum'],$mat['unit']];
            $sum[$mat['material_id']] = $mat[$new];

          }
          
          $new_data = isset($order['materials']) ? $order['materials'] : [];
          
          foreach($new_data as &$order_mat){

            $order_mat['pickup_weight_sum'] = isset($sum[$order_mat['material_id']][0]) ? $sum[$order_mat['material_id']][0] : 0;
            $order_mat['dropoff_weight_sum'] = isset($sum[$order_mat['material_id']][1]) ? $sum[$order_mat['material_id']][1] : 0;
            unset($sum[$order_mat['material_id']]);

          }


          foreach($sum as $key_sum => $value_sum1){

            $array = array();
            $label = \App\Model\Material::where('material_id',$key_sum)->value('name');
            $unit = \App\Model\Unit::where('id',$value_sum1[2])->value('unit');
            $array['value'] = $key_sum;
            $array['remarks'] = "";
            $array['label'] = $label;
            $array['weight'] = 0;
            $array['unit'] = $unit;
            $array['pickup_weight_sum'] = $value_sum1[0];
            $array['dropoff_weight_sum'] = $value_sum1[1];

            array_push($new_data,$array);

          }
          $order['materials'] = $new_data;
          

                                                                  #############
        
        
          

      $trips = [];
      $e_ticket = [];
      $gate_pass = [];
      $droppoff_e_ticket = [];
      $droppoff_gate_pass = [];
      $pickconstraints = [];
      $dropconstraints = [];
      foreach($delivery_trips as &$trip){

        //Change date format for trip created_at, pickup time, pickup checkin time
        
          $created_at = strtotime( $trip['created_at'] );  
          $pickup_time = $trip['pickup_time'] != null ? strtotime( $trip['pickup_time'] ) : null;  
          $pickup_check_in = $trip['pickup_check_in'] != null ? strtotime( $trip['pickup_check_in'] ) : null;  
    
          $trip['created_at'] = date('Y-m-d H:i:s', $created_at );
          $trip['pickup_time'] = $pickup_time != null ? date('Y-m-d H:i:s', $pickup_time ) : null;
          $trip['pickup_check_in'] = $pickup_check_in != null ? date('Y-m-d H:i:s', $pickup_check_in ) : null;
        
        
        if(isset($trip['trip_status']['trip_status_id']) && ($trip['trip_status']['trip_status_id'] != 4 && $trip['trip_status']['trip_status_id'] != 6)){ //Ayesha 04-08-2022 Order can not be completed untill all trips are closed
          $order['can_complete'] = false;
        }
    $e_ticket = [];
        $gate_pass = [];
        
        foreach($trip['pickup_material'] as $tpm){
          $tpm['e_ticket'] != null ? array_push($e_ticket, $tpm['e_ticket']) : null; 
          $tpm['gate_pass'] == null || ($tpm['gate_pass'] == "[]") ?: array_push($gate_pass, $tpm['gate_pass']);
        }
    $droppoff_e_ticket = [];
          $droppoff_gate_pass = [];
        foreach($trip['dropoff_material'] as $tdm){
          $tdm['e_ticket'] != null ? array_push($droppoff_e_ticket, $tdm['e_ticket']) : null;
          $tdm['gate_pass'] == null || ($tdm['gate_pass'] == "[]") ?: array_push($droppoff_gate_pass, $tdm['gate_pass']) ;
        }
        $kpi = array();
        
        isset($trip['trip_startime']) ? array_push($kpi, array("description" => date_format(date_create($trip['trip_startime']), "Y-m-d H:i:s"))) : array_push($kpi, null);
        // isset($trip['pickup_check_in']) ? array_push($kpi, array("description" => date_format(date_create($trip['pickup_check_in']), "Y-m-d h:i:a"))) : array_push($kpi, null);
        isset($trip['pickup_check_in']) ? array_push($kpi, array("description" => $trip['pickup_check_in'])) : array_push($kpi, null);
        isset($trip['load']) ? array_push($kpi, array("description" => date_format(date_create($trip['load']), "Y-m-d H:i:s"), "e-ticket" => $e_ticket, "gate_pass" => $gate_pass)) : array_push($kpi, null);
        isset($trip['dropoff_check_in']) ? array_push($kpi, array("description" => date_format(date_create($trip['dropoff_check_in']), "Y-m-d H:i:s"))) : array_push($kpi, null);
        isset($trip['unload']) ? array_push($kpi, array("description" => date_format(date_create($trip['unload']), "Y-m-d H:i:s"), "e-ticket" => $droppoff_e_ticket, "gate_pass" => $droppoff_gate_pass)) : array_push($kpi, null);
        isset($trip['trip_endtime']) ? array_push($kpi, array("description" => date_format(date_create($trip['trip_endtime']), "Y-m-d H:i:s"))) : array_push($kpi, null);
        isset($trip['pickup_material_sum_weight']) ? array_push($kpi, array("description" => $trip['pickup_material_sum_weight'])) : array_push($kpi, null);
        $trip['kpi'] =   $kpi;
        $start_time = \Carbon\Carbon::parse($trip['load']);
        $finish_time = \Carbon\Carbon::parse($trip['trip_startime']);

        if($trip['load'] != null && $trip['trip_startime'] != null){
          // return $trip['load'];
          $trip['trip_startime'] = \Carbon\Carbon::parse($trip['trip_startime']);
          $trip['load'] = \Carbon\Carbon::parse($trip['load']);
          $diff = $trip['load']->diffInMinutes($trip['trip_startime']);
          
          $calc_hours = intdiv($diff, 60);
          $calc_mins = $diff % 60;
          if($calc_hours <= 0 && $calc_mins <= 0){
            $hours = [];
          }elseif($calc_hours <= 0 && $calc_mins >= 0){
            $hours = ($diff % 60).' mins';
          }else{
            $hours = intdiv($diff, 60).' hours '. ($diff % 60).' mins';
          }
          $trip['eta'] = $hours;  
        }
        else{
          $trip['eta'] = [];  
        }
        
          
        ////////Adding Constraints////////

      $constraint = $trip['constraints'];

      for($i=0;$i<count($constraint);$i++)
      {
        if($constraint[$i]['key']=="PICKUP"){

        $pickupconstraint['delivery_trip_id'] = $constraint[$i]['trip_id'];
        $pickupconstraint['key'] = $constraint[$i]['key'];
        $pickupconstraint['delay'] = $constraint[$i]['locations']['delay'];
        $pickupconstraint['name'] = json_decode($constraint[$i]['locations']['location_name']) ;
        $pickupconstraint['latitude'] = $constraint[$i]['locations']['latitude'] ;
        $pickupconstraint['longitude'] = $constraint[$i]['locations']['longitude'] ;

          
        $pickupconstraint != null ? array_push($pickconstraints, $pickupconstraint) : null;

        }

        else if ($constraint[$i]['key']=="DROPOFF") {
        $dropoffconstraint['delivery_trip_id'] = $constraint[$i]['trip_id'];
        $dropoffconstraint['key'] = $constraint[$i]['key'];
        $dropoffconstraint['delay'] = $constraint[$i]['locations']['delay'];
        $dropoffconstraint['name'] = json_decode($constraint[$i]['locations']['location_name']) ;
        $dropoffconstraint['latitude'] = $constraint[$i]['locations']['latitude'] ;
        $dropoffconstraint['longitude'] = $constraint[$i]['locations']['longitude'] ;
          
        $dropoffconstraint != null ? array_push($dropconstraints, $dropoffconstraint) : null;

        }

        $trip['pickup_constraints'] = $pickconstraints;
        $trip['dropoff_constraints'] = $dropconstraints;
        unset($trip['constraints']);
      }
      
        if(!isset($trip['trip_startime']) && !isset($trip['pickup_check_in']) && !isset($trip['load']) && !isset($trip['dropoff_check_in'])
        && !isset($trip['unload']) && !isset($trip['trip_endtime'])){
          $trip['kpi'] = [];
        }
      }



      $order['trips'] = $delivery_trips;
            
            if(isset($order['pickup'])){
              $latitude1 = $order['pickup']['longitude'];
              $longitude1 = $order['pickup']['latitude'];
              if(isset($order['customer_warehouse'])){
                $latitude2 = $order['customer_warehouse']['longitude'];
                $longitude2 = $order['customer_warehouse']['latitude'];

                $distance = _getDistance($latitude1,$longitude1,$latitude2,$longitude2,6371000);
                $order['distance'] = $distance;
              }
              if(isset($order['aqg'])){
                $latitude2 = $order['aqg']['longitude'];
                $longitude2 = $order['aqg']['latitude'];

                $distance = _getDistance($latitude1,$longitude1,$latitude2,$longitude2,6371000);
                $order['distance'] = $distance;
              }
            }
          
              return response()->json([
                "order_detail" => $order
              ]);
            }
            else{
              return response()->json([
                "Code" => 403,
                "data" => "",
                "Message" => "Order Not Found."
              ]);
            }
        }
        else{
          return response()->json([
            "Code" => 403,
            "data" => "",
            "Message" => "Missing Input."
          ]);
        }
      }
      else{
        return response()->json([
          "Code" => 403,
          "data" => "",
          "Message" => "Invalid Json."
        ]);
      }
    }

  public function orderComplete(Request $request){

    $data = json_decode($request->getContent(),true);

    $validator = Validator::make($request->all(), [
      'order_id' => 'required|integer|exists:orders,order_id',
    ]);
    if ($validator->fails()) {
      return responseValidationError('Fields Validation Failed.', $validator->errors());
    }

    try {
    $order = Order::select('order_status_id','customer_id')->where('order_id',$data['order_id'])->first();
    $prev_status = $order['order_status_id'];
    if($prev_status){
      $prev_status = Order::where('order_id',$data['order_id'])->value('prev_order_status_id');
    }
    Order::where('order_id',$data['order_id'])->update(['prev_order_status_id' => $prev_status, 'order_status_id' => 4]);
    
    }catch (\Exception $ex) {
      $response = [
          "code" => 500,
          "data" => [
              "error" => $ex->getMessage()
          ],
          'message' => 'Error in updating order status to complete.'
      ];
      return response()->json($response);
    }

    $orderlogsdata[] = [

      'order_id' => $data['order_id'],
      'order_status_id' => 4,
      'source_id' => 12,
      'user_id' => $order['customer_id'],
      'created_at' =>  date('Y-m-d H:i:s'),
      'updated_at' =>  date('Y-m-d H:i:s')
    
    
    ];
    
    OrderLogs::insert($orderlogsdata);

    return response()->json([
      "Code" => 200,
      "data" => "",
      "Message" => __("Order has been completed.")
    ]);
  }

  public function orderDetailSupervisor(Request $request)
  {
    $data = json_decode($request->getContent(),true);

    $validator = Validator::make($request->all(), [
      'order_id' => 'required|integer|exists:orders,order_id',
  ]);
  if ($validator->fails()) {
    return responseValidationError('Fields Validation Failed.', $validator->errors());
  }


    $order = Order::with('customer:customer_id,name','order_status','customer_warehouse','aqg','pickup',
    'cancel_id','weight_unit:id,unit','lot')->where('order_id',$data['order_id'])->get();
  
    if($order[0]['pickup'] != null){
      $map_info = array("latitude" => $order[0]['pickup']['latitude'],"longitude" => $order[0]['pickup']['longitude']);
      $map_info = json_encode($map_info);   
      $order[0]['pickup']['map_info'] = $map_info;
    }

    if($order[0]['aqg'] != null){
      $map_info = array("latitude" => $order[0]['aqg']['latitude'],"longitude" => $order[0]['aqg']['longitude']);
      $map_info = json_encode($map_info);
      $order[0]['aqg']['map_info'] = $map_info;
    }
  

    if(isset($order[0]['unit'])){
      $order[0]['unit'] = $order[0]['weight_unit']['unit'];
    }
    if(!$order->isEmpty()){
    $order = $order[0];
    $equipment = OrderServiceRequest::listOfEquipment($data['order_id']);
    
    foreach($equipment as &$item){
      if(isset($item['service_category']) && isset($item['service_category']['items'])){
        foreach($item['service_category']['items'] as &$item1){
          $item1['transaction'] = $item1['transactions'];
          unset($item1['transactions']) ;
        }
      }
    }
    $documents = PlanningDocumentation::getDocList($data['order_id']);
    $tools = OrderServiceRequest::listOfTools($data['order_id']);
    foreach($tools as &$item){
      if(isset($item['service_category']) && isset($item['service_category']['items'])){
        foreach($item['service_category']['items'] as &$item1){

          $item1['transaction'] = $item1['transactions'];
          unset($item1['transactions']) ;
        }
      }
    }
    
    $assets = OrderServiceRequest::listOfAssets($data['order_id']);
    foreach($assets as &$asset){
      if(isset($asset['service_category']) && isset($asset['service_category']['items'])){
      foreach($asset['service_category']['items'] as &$asset1){
        $asset1['transaction'] = $asset1['transactions'];
        unset($asset1['transactions']) ;
      }
    }
  }

  $order['labors'] = OrderServiceRequest::listOfLabors($data['order_id']);


  if($order['category_id'] == 4){ //For adding Inventory TRANSACTION for Skip_Collection
    foreach($assets as &$whatever){
      $whatever['skip']['asset_title'] = $whatever['skip']['asset_inventory']['title'];
      unset($whatever['skip']['asset_inventory']);
      foreach($whatever['service_category']['items'] as &$skip_info){
        if($whatever['skip']['asset_id'] == $skip_info['asset_id']){
          $skip_info['transaction'] = $whatever['skip']['asset_transaction'];
          unset($whatever['skip']['asset_transaction']);
        }
      }
    }
  }

  $order['mobilization'] = array_merge($equipment,$tools,$assets);

  $planning = Planning::where('order_id',$data['order_id'])->select('health_safety','safety_orientation','working_days','working_hours','trucks','trips')->get()->toArray();
  $material = OrderMaterial::with('material_unit:id,unit','material:material_id,name')->where('order_id',$data['order_id'])->whereStatus(1)->get();
  $bridge = OrderServiceRequest::with('service_category:service_category_id,model')->where('order_id',$data['order_id'])->where('service_category_id',10)->whereStatus(1)->get()->toArray();



  foreach($material as $mat){
    $material_data[] = ["value" =>  isset($mat['value']) ? $mat['value'] : null,
      "label" => isset($mat['material']['name']) ? $mat['material']['name'] : null, 
      "remarks" => isset($mat['remarks']) ? $mat['remarks'] : null,
      "weight" => isset($mat['weight']) ? $mat['weight'] : null,
      "unit" => isset($mat['material_unit']['unit']) ? $mat['material_unit']['unit'] : null,
      "unit_id" => isset($mat['material_unit']['id']) ? $mat['material_unit']['id'] : null,
      "length" => isset($mat['length']) ? $mat['length'] : null];
    }
    if(isset($order['labors']) && $order['labors'] != null){
      foreach($order['labors'] as $lab){
        $labor_data[] = ["value" => isset($lab['value']) ? $lab['value'] : null,
        "label" => isset($lab['service_category']['title']) ? $lab['service_category']['title'] : null,
          "name" => "", "nationality" => ""];
      }
    $order['man_power'] = isset($labor_data) ? $labor_data : null;
    } 
    
    $order['types_of_material_to_be_collected'] = isset($material_data) ? $material_data : null;
    $order['trips'] = isset($planning[0]['trips']) ? $planning[0]['trips'] : null;
    $order['trucks'] = isset($planning[0]['trucks']) ? $planning[0]['trucks'] : null;
  
      return response()->json([
        "Code" => 200,
        "data" => ["order_detail" => $order,  
                  "planning" => [
                      "health_safety" => isset($planning[0]['health_safety']) ? $planning[0]['health_safety'] : null ,
                      "safety_orientation" => isset($planning[0]['safety_orientation']) ? $planning[0]['safety_orientation'] : null,
                      "working_hours" => isset($planning[0]['working_hours']) ? $planning[0]['working_hours'] : null,
                      "working_days" => isset($planning[0]['working_days']) ? $planning[0]['working_days'] : null,
                      "load_and_transport" => ["model" => isset($bridge[0]['service_category']['model']) ? $bridge[0]['service_category']['model'] : null],
                      "documents" => isset($documents) ? $documents : null,
                    
                    ],
                  
                  ],
        "message" => __("Data Loaded")
    ]);
  }
  else{
      return response()->json([
        "Code" => 403,
        "data" => "",
        "Message" => "Order Not Found."
      ]);
  }
  }



  public function rawDataCustomer(Request $request)
  {

    $user = auth()->guard('oms')->user();
    $customer_id = ($user->customer_id);  
    try {
    $categories = ServiceCategory::where('parent_id',null)->orderBy('created_at','DESC')->get()->toArray();
    $asset_id = ServiceCategory::where('title', 'like', '%' . "Asset" . '%')->value('service_category_id');
    $sub_categories = ServiceCategory::whereNotNull('parent_id')->where('parent_id','<>',$asset_id)->orderBy('created_at','DESC')->get()->toArray();
    $assets = ServiceCategory::whereNotNull('parent_id')->where('parent_id',$asset_id)->orderBy('created_at','DESC')->get()->toArray();
    $modal = ServiceCategory::select('service_category_id','title','model','capacity','platform_size','made')->where('service_category_id',10)->orderBy('created_at','DESC')->get()->toArray();
    $customer_lots = CustomerLot::select('customer_lot_id','lot_number','address_id','customer_id')->where('customer_id',$customer_id);
    $contracts = CustomerLot::select('customer_lot_id','lot_number','address_id','customer_id')->where('customer_id',$customer_id);
    $materials = Material::with('customer_pricing:customer_id,material_id,price')->where('customer_id', $customer_id);
    $corporate_customer_material = \App\Model\CorporateCustomerMaterial::where('corporate_customer_material.status',1)
    ->join('material', 'material.material_id', '=', 'corporate_customer_material.parent_material_id')
    ->get(['id','corporate_cust_address_id','parent_material_id','child_material_code','child_material_desc','material.name'])
    ->toArray();
    foreach($corporate_customer_material as &$ccm){

      $ccm['child_material_desc'] = $ccm['name'];
      unset($ccm['name']);
    }

    $customer_lots = $customer_lots->orderBy('created_at','DESC')->get()->toArray();
    $contracts = $contracts->orderBy('created_at','DESC')->get()->toArray();
    $materials = $materials->orderBy('created_at','DESC')->get()->toArray();

    $measurement_types = Unit::orderBy('created_at','DESC')->get()->toArray();
    $order = new Order();
    $customers = Customer::where('status',1)->orderBy('created_at','DESC')->get()->toArray();
    $designations = Designation::orderBy('created_at','DESC')->get()->toArray();
  }catch (\Exception $ex) {
    $response = [
        "code" => 500,
        "data" => [
            "error" => $ex->getMessage()
        ],
        'message' => 'Error in fetching raw customer data.'
    ];
    return response()->json($response);
  }
  
    return response()->json([
      "Code" => 200,
      "data" => [
      "categories" => isset($categories) ? $categories : null,
      "sub_categories" => $sub_categories,
      "measurement_types" => $measurement_types,
      "modal" => $modal,
      "materials" => $materials,
      "designations" => $designations,
      "corporate_customer_material" => $corporate_customer_material,
      // "cancel_reasons" => $cancelReason,
      "assets" => $assets,
      // "aqg_warehouses" => $addresses,
      "customers" => $customers,
      "customer_lots" => $customer_lots,
      "contracts" => $contracts,
      // "order_statuses" => $order_statuses
    ],
      "message" => __("List fetched Successfully")
      
    ]);
  }

  public function dashboardData(Request $request){

    $data = $request->all();
    $validator = Validator::make($request->all(), [
      'customer_id' => 'nullable|integer|exists:customers,customer_id',
      'date_from' => 'nullable|date',
      'date_to' => 'required_with:date_from|nullable|date|after:date_from'
    ]);
    if ($validator->fails()) {
      return responseValidationError('Fields Validation Failed.', $validator->errors());
    }


      if(isset($data['customer_id']) && $data['customer_id'] != "" && $data['customer_id'] != null){
        $customer_id = $data['customer_id'];
      }
      else{
        $user = auth()->guard('oms')->user();
        $data['customer_id'] = ($user->customer_id);  
        $customer_id = $data['customer_id'];
      }

    $current_date = Carbon::now();
    $month_date = Carbon::now()->subMonth();
    $data['date_to'] = !isset($data['date_to']) || $data['date_to'] == null ? $current_date->toDateString() : $data['date_to'];
    $data['date_from'] = !isset($data['date_from']) || $data['date_from'] == null ? $month_date->toDateString() : $data['date_from'];
    

    $tripsd = \DB::select(DB::raw("SELECT dailySummary.cday, dailySummary.daily_trips, IFNULL(7days.7Days, 0) as 7Days,IFNULL(30Days.30days, 0) as 30days   from 
    (
    SELECT cday, IFNULL(trips, 0) as daily_trips  from ( SELECT ADDDATE('".$data['date_from']."', INTERVAL @i:=@i+1 DAY) AS cday
    FROM (
    SELECT a.a
    FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c
    ) a
    JOIN (SELECT @i := -1) r1
    WHERE 
    @i < DATEDIFF('".$data['date_to']."', '".$data['date_from']."')) as Dates left JOIN  (SELECT count(delivery_trip_id) as trips, DATE(trip_startime) as trip from delivery_trips  join orders on delivery_trips.order_id = orders.order_id where DATE(trip_startime) BETWEEN '".$data['date_from']."' and '".$data['date_to']."' AND orders.customer_id = ".$customer_id." group by DATE(trip_startime)) daily on daily.trip = Dates.cday) as dailySummary
    left JOIN 
    (select distinct trip, (select count(delivery_trip_id) from delivery_trips T2 join orders on T2.order_id = orders.order_id  where DATE(trip_startime) between date_sub(T1.trip, interval 7 day) and T1.trip AND orders.customer_id = ".$customer_id.") as 7Days from (SELECT ADDDATE('".$data['date_from']."', INTERVAL @j:=@j+1 DAY) AS trip
    FROM (
    SELECT a.a
    FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c
    ) a
    JOIN (SELECT @j := -1) r1
    WHERE 
    @j < DATEDIFF('".$data['date_to']."', '".$data['date_from']."')) T1 ) as 7days
    on 7days.trip = dailySummary.cday 
    left join 
    (select distinct trip, (select count(delivery_trip_id) from delivery_trips T2 join orders on T2.order_id = orders.order_id where DATE(T2.trip_startime) between date_sub(T1.trip, interval 30 day) and T1.trip AND orders.customer_id = ".$customer_id.") as 30Days from (SELECT ADDDATE('".$data['date_from']."', INTERVAL @k:=@k+1 DAY) AS trip
    FROM (
    SELECT a.a
    FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
    CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c
    ) a
    JOIN (SELECT @k := -1) r1
    WHERE 
    @k < DATEDIFF('".$data['date_to']."', '".$data['date_from']."')) T1 ) as 30Days on 30Days.trip =  dailySummary.cday"));

      $array_trips = [array('Date','Daily','Weekly','Monthly')];
    
  foreach($tripsd as $rt){
    $array = (array)$rt;
    array_push($array_trips,array($array['cday'],$array['daily_trips'],$array['7Days'],$array['30days']));
  } 


  //  return $array_trips;
  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////


  ///////////////////////////////////////Vehicles///////////////////////////////////////////////////////////////////
  

                                  ##Graph-Date based##

  if(isset($data['date_from']) && isset($data['date_to']) && $data['date_from'] != null && $data['date_to'] != null){
    $total_vehicles_sd =
    \DB::select(DB::raw("SELECT cday, IFNULL(vehicle_count, 0) as vehicle_count , (select count(*) from customer_approved_vehicles where DATE(customer_approved_vehicles.created_at) <= Dates.cday 
    AND customer_approved_vehicles.customer_id = ".$customer_id." ) as total_vehicles from ( SELECT ADDDATE('2022-05-21', INTERVAL @i:=@i+1 DAY) AS cday
  FROM (
  SELECT a.a
  FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
  CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
  CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c
  ) a
  JOIN (SELECT @i := -1) r1
  WHERE 
  @i < DATEDIFF('".$data['date_to']."', '".$data['date_from']."')) as Dates left JOIN  (Select distinct DATE(trip_startime) as dates,count(*) as vehicle_count  from delivery_trips
  JOIN orders ON delivery_trips.order_id = orders.order_id
    WHERE DATE(trip_startime) BETWEEN '".$data['date_from']."' and '".$data['date_to']."' AND orders.customer_id = ".$customer_id." group by DATE(trip_startime)) daily on daily.dates = Dates.cday Order by Dates.cday ASC
    "));

    
  }else{
    $total_vehicles_sd = DB::table('delivery_trips')
    ->selectRaw("DATE(trip_startime) as trip_dates,count(*) as vehicle_count,
    ( select count(*) from customer_approved_vehicles where customer_approved_vehicles.created_at <= delivery_trips.trip_startime 
    AND customer_approved_vehicles.customer_id = '$customer_id' ) as total_vehicles")
    ->whereRaw('DATE(trip_startime) >= '.$sub_month_date.'')
    ->whereRaw('DATE(trip_startime) <= '.$current_date.'')
    ->groupBy('trip_date')->get();
  }
  // return $total_vehicles_sd;



  $working_vehicles = 0;
  $off_work_vehicles = 0;
  $total_vehicles = 0;
  $vehicle_total_count = 0;
    
    $fleet_data = [array('Date','Used','Assigned')];
    $fleet = [];
    
    foreach($total_vehicles_sd as $veh){
      
      $veh->total_vehicles != 0 ? $vehicle_total_count = $veh->total_vehicles : null;
      $veh->total_vehicles == 0 ? $veh->total_vehicles = $vehicle_total_count : null;


      $off_work = $veh->total_vehicles - $veh->vehicle_count;
      if($off_work < 0){
        $off_work = 0;
      }
      $fleets_array = array($veh->cday , $veh->vehicle_count , $off_work);
      $working_vehicles += $veh->vehicle_count;
      $off_work_vehicles += $off_work;
    
      array_push($fleet_data , $fleets_array);
    }
    $total_vehicles = $working_vehicles + $off_work_vehicles;

  


                                    # # # # # # # # # #

    if(isset($data['date_from']) && isset($data['date_to']) && $data['date_from'] != null && $data['date_to'] != null){ #Without Date Limits

      $avg_material_per_day = DeliveryTrip::select('pickup_materials.weight',DB::raw('DATE(pickup_materials.created_at) AS day'))
                            ->join('orders','orders.order_id','=','delivery_trips.order_id')
                            ->join('pickup_materials','pickup_materials.trip_id','=','delivery_trips.delivery_trip_id')
                            ->where('orders.customer_id',$data['customer_id'])
                            ->where('trip_startime','>=',$data['date_from'])
                            ->where('trip_startime','<=',$data['date_to'])
                            ->groupBy('pickup_materials.created_at')
                            ->distinct('day')
                            ->get();
                            

    }else{
      $avg_material_per_day = DeliveryTrip::select('pickup_materials.weight',DB::raw('DATE(pickup_materials.created_at) AS day'))
                            ->join('orders','orders.order_id','=','delivery_trips.order_id')
                            ->join('pickup_materials','pickup_materials.trip_id','=','delivery_trips.delivery_trip_id')
                            ->where('orders.customer_id',$data['customer_id'])
                            ->where('trip_startime','>=',$sub_month_date)
                            ->where('trip_startime','<=',$current_date)
                            ->groupBy('pickup_materials.created_at')
                            ->distinct('day')
                            ->get();
    }


  $sum = 0;
  $day = 0;
  $count = 0;
  foreach($avg_material_per_day as $avg_day){

    $sum += $avg_day['weight'];
    if($day == 0){
      $day = $avg_day['day'];
      $count++;
    } 
    if($day != $avg_day['day']){
      $day = $avg_day['day'];
      $count++;
    }
      
    }

  if($count > 0){
    $avg_material_per_day = $sum/$count;
    $avg_material_per_day = round($avg_material_per_day,2);
  }else{
    $avg_material_per_day = 0;
  }

                                        ##Percentage##
  // if(isset($data['date_from']) && isset($data['date_to']) && $data['date_from'] != null && $data['date_to'] != null){

  //   $working_vehicles = DeliveryTrip::join('orders','orders.order_id','=','delivery_trips.order_id')
  //   ->where('orders.customer_id',$data['customer_id'])
  //   ->where('trip_startime','>=',$data['date_from'])
  //   ->where('trip_startime','<=',$data['date_to'])
  //   ->count('delivery_trips.vehicle_id');

  // }else{
  
  //   $working_vehicles = DeliveryTrip::join('orders','orders.order_id','=','delivery_trips.order_id')
  //   ->where('orders.customer_id',$data['customer_id'])
  //   ->where('trip_status_id',2)
  //   ->count('delivery_trips.vehicle_id');

  // }


                        
  // $unavailable_vehicles = DB::table('customer_approved_vehicles')->where('customer_id',$data['customer_id'])->whereStatus(9)->count();
  // $total_vehicles = DB::table('customer_approved_vehicles')->where('customer_id',$data['customer_id'])->count();
  // $off_work = $total_vehicles - ($working_vehicles);

  $off_work_percentage = $off_work_vehicles > 0 && $total_vehicles > 0 ? ($off_work_vehicles/$total_vehicles) * 100 : 0;
  // $unavailable_vehicles_percentage = ($unavailable_vehicles/$total_vehicles) * 100;
  $working_vehicles_percentage = $working_vehicles > 0 && $total_vehicles > 0 ? ($working_vehicles/$total_vehicles) * 100 : 0;
  if($working_vehicles_percentage < 0){
    $working_vehicles_percentage = 0;
  }
  if($off_work_percentage < 0){
    $off_work_percentage = 0;
  }

                                      # # # # # # # # # #


  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////



  ///////////////////Number of trips w.r.t Sites/////////////////////////////////////////////////////////////////
  
  if(isset($data['date_from']) && isset($data['date_to']) && $data['date_from'] != null && $data['date_to'] != null){

  $show = Address::select('addresses.address_title','addresses.address_id','addresses.latitude','addresses.longitude',
                  'pickup_materials.weight','delivery_trips.delivery_trip_id')
                  ->join('orders','orders.pickup_address_id','=','addresses.address_id')
                  ->join('delivery_trips','delivery_trips.order_id','=','orders.order_id')
                  ->join('pickup_materials','pickup_materials.trip_id','=','delivery_trips.delivery_trip_id')
                  ->where('orders.customer_id',$data['customer_id'])
                  ->where('trip_startime','>=',$data['date_from'])
                  ->where('trip_startime','<=',$data['date_to'])
                  ->get();

  }
  else{
    $show = Address::select('addresses.address_title','addresses.address_id','addresses.latitude','addresses.longitude',
                  'pickup_materials.weight','delivery_trips.delivery_trip_id')
                  ->join('orders','orders.pickup_address_id','=','addresses.address_id')
                  ->join('delivery_trips','delivery_trips.order_id','=','orders.order_id')
                  ->join('pickup_materials','pickup_materials.trip_id','=','delivery_trips.delivery_trip_id')
                  ->where('orders.customer_id',$data['customer_id'])
                  ->where('trip_startime','>=',$sub_month_date)
                  ->where('trip_startime','<=',$current_date)
                  ->get(); 
  }


                  $grouped = $show->mapToGroups(function ($item, $key) {
                    return [$item['address_id'] => $item];
                  });
                  
                  $act_arr = [];
                  $act_prep = [];
                  $weight = 0;
                  $array_trip = [];
                  foreach($grouped as $key => $gr){
                    foreach($gr as &$gr1){
                      if(!in_array($gr1['delivery_trip_id'] , $array_trip)){
                        array_push($array_trip , $gr1['delivery_trip_id']);
                      }
                      $weight += $gr1['weight'];
                      $act_arr['site_name'] = $gr1['address_title'];
                      $act_arr['no_of_trips'] = count($array_trip);
                      $act_arr['weight'] = $weight;
                      $act_arr['lat'] = $gr1['latitude'];
                      $act_arr['long'] = $gr1['longitude'];
                    }

                    $weight = 0;
                    array_push($act_prep, $act_arr);
                }
                $total_trips = 0;
                $active_sites = count($act_prep);
                foreach($act_prep as $t_trips){
                  $total_trips += $t_trips['no_of_trips'];
                }

    $trips  = array_column($act_prep, 'no_of_trips');

    array_multisort($trips, SORT_DESC, $act_prep);


    // if(isset($data['date_from']) && isset($data['date_to']) && $data['date_from'] != null && $data['date_to'] != null){

    //   $active_sites = Address::join('orders','orders.pickup_address_id','=','addresses.address_id')
    //   ->join('delivery_trips','orders.order_id','=','delivery_trips.order_id')
    //   ->join('pickup_materials','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')
    //   ->where('orders.customer_id',$data['customer_id'])
    //   ->where('delivery_trips.trip_startime','>=',$data['date_from'])
    //   ->where('delivery_trips.trip_startime','<=',$data['date_to'])
    //   ->select('addresses.address')
    //   ->distinct()
    //   ->count('addresses.address');

    // }else{
      
    //   $active_sites = Address::join('orders','orders.pickup_address_id','=','addresses.address_id')
    //   ->join('delivery_trips','orders.order_id','=','delivery_trips.order_id')
    //   ->join('pickup_materials','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')
    //   ->where('orders.customer_id',$data['customer_id'])
    //   ->where('delivery_trips.trip_status_id',2)
    //   ->select('addresses.address')
    //   ->distinct()
    //   ->count('addresses.address');

    // }
    $activity_data = $act_prep;
  
  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////



  ///////////////////////////////Material Summary///////////////////////////////////////////////////////////////////////

  if(isset($data['date_from']) && isset($data['date_to']) && $data['date_from'] != null && $data['date_to'] != null){
    $matches = Order::join('delivery_trips','orders.order_id','=','delivery_trips.order_id')
    ->join('pickup_materials','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')
    ->join('material','pickup_materials.material_id','=','material.material_id')
    ->select('pickup_materials.material_id','material.name as material_name','pickup_materials.weight',
    'pickup_materials.unit')
    ->where('orders.customer_id',$data['customer_id'])
    ->where('pickup_materials.created_at','>=',$data['date_from'])
    ->where('pickup_materials.created_at','<=',$data['date_to'])
    ->get();
  }
  else{
    $matches = Order::join('delivery_trips','orders.order_id','=','delivery_trips.order_id')
    ->join('pickup_materials','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')
    ->join('material','pickup_materials.material_id','=','material.material_id')
    ->select('pickup_materials.material_id','material.name as material_name','pickup_materials.weight',
    'pickup_materials.unit')
    ->where('orders.customer_id',$data['customer_id'])
    ->where('pickup_materials.created_at','>=',$sub_month_date)
    ->where('pickup_materials.created_at','<=',$current_date)
    ->get();
  }

            
  $grouped = $matches->mapToGroups(function ($item, $key) {
    return [$item['material_name'] => $item];
  });

  $material_max_count = 0;
  foreach($grouped as $key_m => &$value_m){
    $weight = 0;
    foreach($value_m as $key_w => &$value_w){
      $weight += $value_w['weight']; 
      unset($value_m[$key_w]); 
    }
    $value_m['weight'] = $weight;
  }
  // return $grouped;

  $array_m = [];
  $material_data = [];

  foreach($grouped as $key_g => $m_group){
  $array_m['name'] = $key_g;
  $array_m['value'] = $m_group['weight'];
  $material_max_count = $m_group['weight'] > $material_max_count ? $m_group['weight'] : $material_max_count; 
  array_push($material_data,$array_m);
  }

  $array = [];
  $activity_data = [];
  $no_of_trips_max_count = 0;
  $weight_max_count = 0;
  $collected_material = 0;
  foreach($act_prep as $item){

  $no_of_trips_max_count = $item['no_of_trips'] > $no_of_trips_max_count ? $item['no_of_trips'] : $no_of_trips_max_count; 
  $weight_max_count = $item['weight'] > $weight_max_count ? $item['weight'] : $weight_max_count; 

  $collected_material += $item['weight'];

  }





  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
  //////////////////////////Formatting Results/////////////////////////////////////////////////////////////////////////////
    $fleet_summary = [];
    $status_count = array('Status','Count');
    $Working = array('Used' , round($working_vehicles_percentage, 2));
    // $unavailable = array('Un-Available' , round($unavailable_vehicles_percentage, 2));
    $offwork = array('Assigned' , round($off_work_percentage, 2));
    array_push($fleet_summary,$status_count,$Working,$offwork);
    $material_summary = [
      'data' => $material_data,
      'material_max_count' => $material_max_count
      ];
      
    $summary = [ 
      'active_sites' => $active_sites,
      'total_trips' => $total_trips,
      'collected_material' => $collected_material,
      'avg_material_per_trip' => $total_trips > 0 ? round($collected_material/$total_trips,2) : 0,
      'avg_collection_per_day' => $avg_material_per_day// problem
    ];



    $fleet = $fleet_data;
    
    
    $fleets = [
      'data' => $fleet,
      'summary' => $fleet_summary
    ];

    $activity_data = ['data' => $act_prep,
    'no_of_trips_max_count' => $no_of_trips_max_count,
    'weight_max_count' => $weight_max_count,
    'summary' => $summary];

    
  ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
  /////////////////////apply limit to number of sites and material///////////////////////////////////////////////////

    $trips_per_site = $activity_data;
    $trips_per_site['data'] = array_slice($trips_per_site['data'], 0, 10);
    $trips_per_site['data'] = collect($trips_per_site['data'])->sortBy('no_of_trips')->reverse()->toArray();

    $activity_data['data'] = array_slice($activity_data['data'], 0, 7);
    $activity_data['data'] = collect($activity_data['data'])->sortBy('no_of_trips')->reverse()->toArray();

    $material_summary['data'] = array_slice($material_summary['data'], 0, 15);
    $material_summary['data'] = collect($material_summary['data'])->sortBy('value')->reverse()->toArray();
    // $material_summary = (array) $material_summary;
    $material_summary['data'] = array_values($material_summary['data']);

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

          return response()->json([
            "Code" => 200,
            "data" => [
              "activity" => $activity_data,
              "trips_per_site" => $trips_per_site,
              "fleet" => $fleets,
              "trips" => $array_trips,
              'material_summary' => $material_summary,
              
            ],
            "message" => __("Data fetched Successfully")
          ]);
  }

  public function rawDataSupervisor(Request $request)
  {

    $categories = ServiceCategory::where('parent_id',null)->orderBy('created_at','DESC')->get()->toArray();
    $sub_categories = ServiceCategory::with('items')->whereNotNull('parent_id')->orderBy('created_at','DESC')->get()->toArray();
    $assets = ServiceCategory::with('items')->whereNotNull('parent_id')->whereIn('parent_id',function($query) {
      $query->select('service_category_id')
              ->from('service_category')->where('key', 'like', '%' . "ASSETS" . '%');
    })->orderBy('created_at','DESC')->get()->toArray();
    // $status_ids = getOrderStatus('getStatusId');

    $status_ids = getStatusIds(['CANCELLED','COMPLETED']);
    $temp_assigned_assets = OrderServiceRequest::with('order')->whereHas('order', function($query) use($status_ids){
      $query->whereNotIn('order_status_id',$status_ids);
    })->where('temp_assets','!=',null)->pluck('temp_assets');
    
    $temp_array = Arr::collapse($temp_assigned_assets);
    $yard_assigned_assets = yardAssignedAssets();
    
    $skips = Skip::with('material','asset_inventory.service_category')->where('customer_id',null)->whereIn('asset_id',$yard_assigned_assets)->whereNotIn('asset_id',$temp_array)->orderBy('created_at','DESC')->get()->toArray();   
    $modal = ServiceCategory::select('service_category_id','title','model','capacity','platform_size','made')->where('service_category_id',10)->orderBy('created_at','DESC')->get()->toArray();
    $materials = Material::with('customer_pricing:material_id,price,quantity')->get()->toArray();
    $order_statuses = OrderStatus::whereStatus(1)->whereNotIn('order_status_id',[2,13,14])->orderBy('created_at','DESC')->get()->toArray();
    $measurement_types = Unit::orderBy('created_at','DESC')->get()->toArray();
    $document_types = DocumentType::orderBy('created_at','DESC')->get()->toArray();
    $order = new Order();
    $logistics = Vehicle::orderBy('created_at','DESC')->get()->toArray();
    $customers = Customer::where('status',1)->orderBy('created_at','DESC')->get()->toArray();
    $getReasons = $order->getCancelOrderReasons();
    $cancelReason = array();

    if(!empty($getReasons)){
      for($i=0; $i < count($getReasons) ;$i++){
        $reason = json_decode($getReasons[$i]['reason']);
        array_push($cancelReason, array(
          "id" => (string)$getReasons[$i]['cancel_reason_id'],
          "reason_ar" => $reason-> ar,
          "reason_en" => $reason-> en,
          "erp_id" => (string)$getReasons[$i]['erp_id'],
          "status" => (string)$getReasons[$i]['status'],
          "mobile_visible" => $getReasons[$i]['mobile_visible'],
          "sdt" => $getReasons[$i]['created_at'],
          "udt" => "",
          "ticket_category_id" => (string)$getReasons[$i]['ticket_category_id'],
          "client_sort_id" => (string)$getReasons[$i]['client_sort_id'],
        ));
      }

    }
    $designations = Designation::get()->toArray();
    $addresses = Store::whereStatus(1)->orderBy('created_at','DESC')->get()->toArray();
    $customer_sites = Address::select('address_id','customer_id','address','address_title')->whereStatus(1)->orderBy('created_at','DESC')->get()->toArray();

    return response()->json([
      "Code" => 200,
      "data" => ["categories" => isset($categories) ? $categories : null,
      "sub_categories" => $sub_categories,
      "measurement_types" => $measurement_types,
      "modal" => $modal,
      "materials" => $materials,
      "designations" => $designations,
      "cancel_reasons" => $cancelReason,
      "assets" => $assets,
      "aqg_warehouses" => $addresses,
      "customer_sites" => $customer_sites,
      "customers" => $customers,
      "document_types" => $document_types,
      "logistics" => $logistics,
      "order_statuses" => $order_statuses,
      "skips" => $skips
    ],
      "message" => __("List fetched Successfully")
      
    ]);
  }

  public function cancelOrder(Request $request){
  $message = Order::cancelOrders($request);
  return $message;
  }

  public function getPaymentType($paymentType){
      $mobile = new Mobile();
      $paymentType =  $mobile->clean_sqlwords($paymentType);
    
      if($paymentType == 1){
        $paymentType = 'CASH_ON_DELIVERY';
      }
      else if($paymentType == 2){
        $paymentType = 'CUSTOMER_CREDIT';
      }
      else if($paymentType == 4){
        $paymentType = 'WALLET';
      }
      else if($paymentType == 12){
        $paymentType = 'CREDIT_CARD';
      }
      else if($paymentType == 6){
        $paymentType = 'APPLE_PAY';
      }
      else if($paymentType == 13){
        $paymentType = 'MADA';
      }
      else if($paymentType == 14){
        $paymentType = 'STC_PAY';
      }
      else if($paymentType == 15){
        $paymentType = 'CARD_ON_DELIVERY';
      }
      else{
        $paymentType = 'CASH_ON_DELIVERY';
      }
    
      return $paymentType;
    }
    
    public function setFOCItems($order_id, $foc = [], $user_id)
  {
    // dd($foc);
    $customer = \App\Model\Customer::find($user_id);
    if($customer->account_type_id == 2){
      $customer = \App\Model\Customer::find($customer->parent_id);
    }
    $all_promotions = \App\Model\Promotion::where('status',1)
    ->where('start_date','<=', date('Y-m-d H:i:s'))
    ->where('end_date','>=', date('Y-m-d H:i:s'))
    ->where('group_id', $customer->group_id)
    ->where('channel_id', $customer->channel_id)
    ->get()
    ->toArray();
    for($i=0, $count = count($all_promotions); $i < $count; $i++){
      $all_promotions[$i]['locations'] = json_decode($all_promotions[$i]['locations']);
      $all_promotions[$i]['on_products'] = json_decode($all_promotions[$i]['on_products']);
      // $all_promotions[$i]['gift_products'] = json_decode($all_promotions[$i]['gift_products']);
      if($all_promotions[$i]['range_limit'] != Null){
        $all_promotions[$i]['range_limit'] = json_decode($all_promotions[$i]['range_limit']);
      }
    }
    if(count($all_promotions)){
      $promo_prod = \App\Model\OrderItem::where('order_id', $order_id)->get();

      if(count($foc) > 0){
        foreach ($foc as $key => $value) {
          $item = \App\Model\OrderItem::where('order_id', $order_id)->where('product_id', $value['id'])->get()->first();
          if(is_object($item)){
            $item->foc_items += (int) $value['quantity'];
            $item->save();
          }
          else{
            if($value['quantity'] > 0){
              $model = new \App\Model\OrderItem();
              $model->order_id = $order_id ;
              $model->product_id = (int) $value['id'] ;
              $model->quantity = 0 ;
              $model->foc_items = (int) $value['quantity'] ;
              $model->unit_price = 0 ;
              $model->price = 0 ;
              $model->status = 1 ;
              $model->save();
            }
          }
        }
      }
    }
  }

  public function pendingOrders(){
    $pending_orders = Order::with('customer','order_status')->where('order_status_id',5)->orderBy('created_at','DESC')->paginate(15)->toArray();
    // $ready_for_approval = Order::with('customer','order_status')->where('order_status_id',17)->get()->toArray();
    
    $tools = Tool::get()->toArray();
    $vehicles = Vehicle::whereNotIn('vehicle_type_id', [59,60])->orderBy('created_at','DESC')->get()->toArray();
    $equipments = Vehicle::whereIn('vehicle_type_id', [59,60])->orderBy('created_at','DESC')->get()->toArray();
    $labor = User::where('group_id',21)->orderBy('created_at','DESC')->get()->toArray();
    return response()->json([
      "Code" => 200,
      "data" => ["pending_orders" => $pending_orders,
      // "ready_for_approval" => $ready_for_approval,
      "tools" => $tools,
      "equipments" => $equipments,
      "labor" => $labor,
      "vehicles" => $vehicles
    ],
    "message" => __("Data fetched Successfully")
  ]);
  }

  public function logisticsOrders(Request $request){

    $data =  $request->all();
    $data['perPage'] = isset($data['perPage']) && $data['perPage'] != "" ? $data['perPage'] : 10;
    $user = (Auth::user());
    $user_id = ($user->user_id);
    // $assigned_to_cust = Customer::where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id)->get()->toArray();
    $ids = ServiceCategory::where('representative_id' , $user_id)->pluck('service_category_id'); 
    $orders = Order::with('customer:customer_id,name','order_status','createdBy:user_id,first_name,last_name')
    ->whereHas('orderServiceRequests', function ($query) use($ids){
      return $query->whereIn('service_category_id', $ids);
    });
    
    
    if(checkIfAdmin($user_id) == false){
      $orders->whereHas('customer', function($query) use($user_id){
        $query->where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id);
      });
    }
    $orders->whereIn('order_status_id',[16])->orderBy('created_at','DESC');
 

  if(isset($data['request_number']) && $data['request_number'] != ""){
    $orders->where('order_number',$data['request_number']);
  }
  if(isset($data['customer']) && $data['customer'] != ""){
    $orders->where('customer_id',$data['customer']);
  }
  if(isset($data['required_start_date']) && $data['required_start_date'] != ""){
      $orders->where('required_start_date',">=",$data['required_start_date']);
  }
  if(isset($data['estimated_end_date']) && $data['estimated_end_date'] != ""){
      $orders->where('estimated_end_date',">=",$data['estimated_end_date']);
  }

  if(isset($data['from']) && $data['from'] != ""){
    $orders->whereDate('created_at','>=',$data['from']);
  }
  if(isset($data['to']) && $data['to'] != ""){
    $orders->whereDate('created_at','<=',$data['to']);
  }


  $orders = $orders->paginate($data['perPage'])->toArray();


  return response()->json([
    "Code" => 200,
    "data" => [
          'orders' => $orders
          ],
  "message" => __("Data fetched Successfully")
  ]);
  }


  public function allOrdersSupervisor(Request $request){

    $data =  $request->all();
    $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;
    $user = (Auth::user());
    $user_id = ($user->user_id);
    // $assigned_to_cust = Customer::where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id)->get()->toArray();
    
    $orders = Order::with('customer:customer_id,name','order_status',
              'createdBy:user_id,first_name,last_name','category:category_id,category_name,key',
              'orderServiceRequests:order_service_request_id,order_id,service_category_id,quantity,is_client_approval_required,is_govt_approval_required,material_id',
              'orderServiceRequests.material:material_id,material_code,name','weight_unit',
              'orderServiceRequests.service_category:service_category_id,title,key',
              'pickup:address_id,address,address_title,customer_id',
              'customer_dropoff:address_id,address,address_title,customer_id',
              'aqg:store_id,store_name,address',
              'site_location:address_id,address,address_title,customer_id','pickupMaterial')
             
              ->withSum('pickupMaterial', 'weight')
              ->withSum('dropoffMaterial', 'weight')
              ->orderBy('created_at','DESC');
            if(checkIfAdmin($user_id) == false){
              $orders->whereHas('customer', function($query) use($user_id){
                $query->where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id);
              });
            } 
    
    if(isset($data['request_number']) && $data['request_number'] != ""){
      $orders->where('order_number',$data['request_number']);
    }

    if(isset($data['customer']) && $data['customer'] != ""){
      $data['customer'] = array_map('intval', explode(',', $data['customer']));
      $orders->whereIn('customer_id',$data['customer']);
    }
    if(isset($data['required_start_date_from']) && $data['required_start_date_from'] != ""){
      $orders->where('required_start_date',">=",$data['required_start_date_from']);
    }
    if(isset($data['required_start_date_to']) && $data['required_start_date_to'] != ""){
        $orders->where('required_start_date',"<=",$data['required_start_date_to']);
    }
    if(isset($data['estimated_end_date_from']) && $data['estimated_end_date_from'] != ""){
        $orders->where('estimated_end_date',">=",$data['estimated_end_date_from']);
    }
    if(isset($data['estimated_end_date_to']) && $data['estimated_end_date_to'] != ""){
        $orders->where('estimated_end_date',"<=",$data['estimated_end_date_to']);
    }
      if(isset($data['status']) && $data['status'] != ""){
        $data['status'] = array_map('intval', explode(',', $data['status']));
        $orders->whereIn('order_status_id',$data['status']);
    }
      if(isset($data['from']) && $data['from'] != ""){
        $orders->whereDate('created_at','>=',$data['from']);

    }
      if(isset($data['to']) && $data['to'] != ""){
        $orders->whereDate('created_at','<=',$data['to']);

    }

    if(isset($data['pickup_site']) && $data['pickup_site'] != ""){
      $orders->where('pickup_address_id',$data['pickup_site'])
            ->orWhere('site_location',$data['pickup_site']);
  }
      $orders = $orders->paginate($data['perPage'])->toArray();
      foreach($orders['data'] as &$order){
        if((!isset($order['pickup']) || $order['pickup'] == null) && (isset($order['site_location'])
        && $order['site_location'] != null)){
          $order['pickup']['address_id'] = $order['site_location']['address_id'];
          $order['pickup']['address'] = $order['site_location']['address'];
          $order['pickup']['address_title'] = $order['site_location']['address_title'];
          $order['pickup']['customer_id'] = $order['site_location']['customer_id'];
        }
      }

      $weight = 0;
      $order_ids = [];
      foreach($orders['data'] as &$order){

        $order['unit'] = isset($order['weight_unit']['unit']) ? $order['weight_unit']['unit'] : null;
        array_push($order_ids , $order['order_id']);
        foreach($order['pickup_material'] as &$pickup_material){
          $weight += $pickup_material['weight'];
        }
        $order['net_weight'] = $weight;
        $weight = 0;
      }
      
      $planning = Planning::whereIn('order_id',$order_ids)->pluck('trucks','order_id');
      // $trucks = DB::table('delivery_trips')->whereIn('order_id',$order['order_id'])->distinct('vehicle_id')->count('vehicle_id');
      $trucks_actual_no = DB::table('delivery_trips')->selectRaw('order_id, count(vehicle_id) as count')->whereIn('order_id',$order_ids)->distinct('vehicle_id')->groupBy('order_id')->pluck('count','order_id');
      

      foreach($orders['data'] as &$order1){

        $order1['trucks'] = isset($planning[$order1['order_id']]) ? $planning[$order1['order_id']] : null;
        if(isset($order1['order_status_id']) && $order1['order_status_id'] == 4){
          $order1['trucks'] = isset($trucks_actual_no[$order1['order_id']]) ? $trucks_actual_no[$order1['order_id']] : $order1['trucks'];
        }

      }
      


    return response()->json([
      "Code" => 200,
      "data" => [
      "orders" => $orders
    
    ],
    "message" => __("Data fetched Successfully")
  ]);
  }


  public function listForSupervisor(Request $request){

    $data =  $request->all();
    $pending_orders = Order::with('customer:customer_id,name','order_status')->whereIn('order_status_id',[5])->orderBy('created_at','DESC')->paginate(100)->toArray();
    $accepted_orders = Order::with('customer:customer_id,name','order_status')->whereIn('order_status_id',[16,15])->orderBy('created_at','DESC');

    $tools = Tool::get()->toArray();
    $vehicles = Vehicle::whereNotIn('vehicle_type_id', [59,60])->orderBy('created_at','DESC')->get()->toArray();
    $equipments = Vehicle::whereIn('vehicle_type_id', [59,60])->orderBy('created_at','DESC')->get()->toArray();
    $labor = User::where('group_id',21)->orderBy('created_at','DESC')->get()->toArray();
    $material = Material::get()->toArray();

    if(isset($data['request_number']) && $data['request_number'] != ""){
      $accepted_orders->where('order_number',$data['request_number']);
    }
    if(isset($data['customer']) && $data['customer'] != ""){
      $accepted_orders->where('customer_id',$data['customer']);
    }
      if(isset($data['required_start_date']) && $data['required_start_date'] != ""){
          $accepted_orders->where('required_start_date',">=",$data['required_start_date']);
    }
    if(isset($data['estimated_end_date']) && $data['estimated_end_date'] != ""){
        $accepted_orders->where('estimated_end_date',">=",$data['estimated_end_date']);
    }
      if(isset($data['status']) && $data['status'] != ""){
        $accepted_orders->where('order_status_id',$data['status']);
    }
      if(isset($data['from']) && $data['from'] != ""){
        $accepted_orders->whereDate('created_at','>=',$data['from']);

    }
      if(isset($data['to']) && $data['to'] != ""){
        $accepted_orders->whereDate('created_at','<=',$data['to']);

    }
      $accepted_orders = $accepted_orders->paginate(100)->toArray();
    
      


    return response()->json([
      "Code" => 200,
      "data" => [
      "pending_orders" => $pending_orders,
      "approved_orders" => $accepted_orders,
      "tools" => $tools,
      "equipments" => $equipments,
      "labor" => $labor,
      "vehicles" => $vehicles,
      "material" => $material,
    
    ],
    "message" => __("Data fetched Successfully")
  ]);
  }

    public function getCustomerOrders(Request $request) {

      $data =  json_decode($request->getContent(),true);

      if($data == ""){
        return response()->json([
          "Code" => 403,
          "data" => "",
          "Message" => "Invalid json."
        ]);
      }

      if(!isset($data['user_id']) || empty($data['user_id'])){
        return response()->json([
          "Code" => 403,
          "data" => "",
          "Message" => "Missing Input."
        ]);
      }
      $user = auth()->guard('oms')->user();
      if($user->customer_id != $data['user_id']){
        return response()->json([
          "Code" => 403,
          "data" => "",
          "Message" => "Unauthorized User."
        ]);
      }

      $orders = \App\Model\Order::with('pickup', 'customer_dropoff', 'order_status','category:category_id,category_name,key',
      'pickupMaterial','weight_unit:id,unit')
      ->withSum('pickupMaterial', 'weight')
      ->withSum('dropoffMaterial', 'weight')
      ->where('customer_id', $data['user_id'])->orderBy('created_at','DESC');
    


      if(isset($data['request_number']) && $data['request_number'] != ""){
        $orders->where('order_number',$data['request_number']);
      }
    
      if(isset($data['pickup_site']) && $data['pickup_site'] != ""){
          $orders->where('pickup_address_id',$data['pickup_site']);
      }

      if(isset($data['required_start_date_from']) && $data['required_start_date_from'] != ""){
        $orders->where('required_start_date',">=",$data['required_start_date_from']);
    }
    if(isset($data['required_start_date_to']) && $data['required_start_date_to'] != ""){
        $orders->where('required_start_date',"<=",$data['required_start_date_to']);
    }
    if(isset($data['estimated_end_date_from']) && $data['estimated_end_date_from'] != ""){
        $orders->where('estimated_end_date',">=",$data['estimated_end_date_from']);
    }
    if(isset($data['estimated_end_date_to']) && $data['estimated_end_date_to'] != ""){
        $orders->where('estimated_end_date',"<=",$data['estimated_end_date_to']);
    }

      if(isset($data['from']) && $data['from'] != ""){
        $orders->whereDate('created_at','>=',$data['from']);
      }
      if(isset($data['to']) && $data['to'] != ""){
        $orders->whereDate('created_at','<=',$data['to']);
      }

      $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 15;

      $orders = $orders->paginate($data['perPage'])->toArray();

      $weight = 0;
      $order_ids = [];

      foreach($orders['data'] as &$order){

        $order['unit'] = isset($order['weight_unit']['unit']) ? $order['weight_unit']['unit'] : null;
        array_push($order_ids , $order['order_id']);
        foreach($order['pickup_material'] as &$pickup_material){
          $weight += $pickup_material['weight'];
        }
        $order['net_weight'] = $weight;
        $weight = 0;
      }
      
      $planning = Planning::whereIn('order_id',$order_ids)->pluck('trucks','order_id');
      // $trucks = DB::table('delivery_trips')->whereIn('order_id',$order['order_id'])->distinct('vehicle_id')->count('vehicle_id');
      
      $trucks_actual_no = DB::table('delivery_trips')->selectRaw('order_id, count(distinct(vehicle_id)) as count')->whereIn('order_id',$order_ids)->groupBy('order_id')->pluck('count','order_id');
      
      foreach($orders['data'] as &$order1){

        $order1['trucks'] = isset($planning[$order1['order_id']]) ? $planning[$order1['order_id']] : null;
        if(isset($order1['order_status_id']) && $order1['order_status_id'] == 4){
          $order1['trucks'] = isset($trucks_actual_no[$order1['order_id']]) ? $trucks_actual_no[$order1['order_id']] : $order1['trucks'];
        }

      }
    
      return response()->json([
        "Code" => 200,
        "data" => ["orders" => $orders]
      ]);
    }

    function view(Request $request, $orderId){

      $order = new Order();

      $orderstatuses = OrderStatus::where('status', 1)->get()->toArray();

      $cancelReasons = \App\Model\CancelReason::where([
        ["mobile_visible", "=", 1]
        ])->get()->toArray();

        $customer = session("customer");

        $hide_price = 0;
        if($customer){
          $hide_price = $customer['hide_price'];
        }

        try {
          $orderDetails = \App\Model\Order::with(['log.user', 'log.action','log.log_status_detail','log.log_source',
          'customer', 'customer.parent', 'address', 'items.variant','items.product','order_status_detail', 'payment.detail', 'deliverySlots',
          'payment_method_info','order_status','recurring','qitaf:order_id,qitaf_rewardpoints','wallet:id,order_id,amount',
          'promocode:promocode_id,type'])->where('order_number',$orderId)->first();
          $account_type = $orderDetails->customer;
          $account_type_id = $account_type->account_type_id;
          $style_show_corporate = 'display:block;';
          $style_corporate = 'display:block;';
          if($account_type_id != 0){
            $check_corporate = Option::getValueByKey('SHOW_CORPORATE_DELIVERY_SLOT');
            if($check_corporate != 1){
              $style_show_corporate = 'display:none;';
            }else{
              $style_corporate = 'display:none;';
            }
          }
          if($orderDetails){
            $orderDetails = $orderDetails->toArray();
          }else{
            return redirect()->route('order.list');
          }
        } catch (\Exception $e) {
          return redirect()->route('order.list');
        }
        if($orderDetails['customer_id'] != $customer['customer_id']){
          $parent_check = \App\Model\Customer::where('parent_id',$customer['customer_id'])->pluck('customer_id')->toArray();
          if(!in_array($orderDetails['customer_id'],$parent_check)){
            return redirect()->route('order.list');
          }
        }
        return view('yaa_layouts.order_detail_yaa', ['order' => $orderDetails, 'cancelReasons'=>$cancelReasons, 'hide_price' => $hide_price,"step" =>4, "orderstatuses" => $orderstatuses,
        "style_show_corporate" => $style_show_corporate,
        "style_corporate" => $style_corporate  ]);
      }

      public function approveOrder(Request $request) {

        $data =  json_decode($request->getContent(),true);
  
        $validator = Validator::make($request->all(), [
          'order_id' => 'required|integer|exists:orders,order_id'
        ]);
    
        if ($validator->fails()) {
          return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
  
        $category_name = checkOrderCatgeory($data['order_id']);

        if($category_name == "ASSET" || $category_name == "SKIP_COLLECTION"){
          
          $status_id = getStatusId("ACCEPTED");
          $customer_id = \App\Model\Order::where('order_id', $data['order_id'])->value('customer_id');
          \App\Model\Order::where('order_id', $data['order_id'])->update(['order_status_id' => $status_id]);
          $orderlogsdata[] = [

            'order_id' => $data['order_id'],
            'order_status_id' => $status_id,
            'source_id' => 12,
            'user_id' => $customer_id,
            'created_at' =>  date('Y-m-d H:i:s'),
            'updated_at' =>  date('Y-m-d H:i:s')
          
          
          ];
          
          OrderLogs::insert($orderlogsdata);
          return response()->json([
            "code" => 200,
            "data" => "",
            "message" => "Order status updated Successfully"
          ]);

        }else{

          return response()->json([
            "code" => 500,
            "data" => "",
            "message" => "Order cannot be updated"
          ]);

        }
       
        
       
      
  
  
       
      
       
      }

  
  

}
