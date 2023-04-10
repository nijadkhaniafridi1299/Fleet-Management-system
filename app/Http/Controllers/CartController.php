<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use  App\Model\Product;
use  App\Model\Category;
use  App\Model\Location;
use  App\Model\DeliverySlot;
use  App\Model\CustomerDeliverySlot;
use  App\Model\Option;
use  App\Model\Customer;
use  App\Model\Order;
use App\Model\ExtCompContract ;
use App\Model\Promocode ;
use App\Model\SimilarProduct ;
use App\Model\CustomerPaymentReg ;
use App\Model\MobileAddress as Address ;
use App\Model\SaveOrder ;
use App\Model\MobilePayment ;
use App\Model\OrderItem ;
use App\Payment\HyperPay;
use App\Http\Middleware\SaveMobileRequest as Mobile;
use App\Model\MobileOrder as GetOrder;
use App\Model\WalletTransaction ;
use App\CheckPromocode ;




class CartController extends Controller

{
  protected $Last3HourOrders = array();
  public function getCartParameters(Request $request)
  {
    $paymentsMethods = [];

    $data =  json_decode($request->getContent(),true);
    if($data == ""){
      return response()->json([
        "Code" => 403,
        "Message" => "Invalid json."
      ]);
    }

    if(!isset($data['user_id'])){
      return response()->json([
        "Code" => 403,
        "Message" => "Missing Input."
      ]);
    }
    $user = auth()->guard('oms')->user();
  if($user->customer_id != $data['user_id']){
    return response()->json([
      "Code" => 403,
      "Message" => "Unauthorized User."
    ]);
  }

    $app_version = ($request->header('AppVersion'))?$request->header('AppVersion'):"2.1";
    $device = $request->header('DeviceType');
    $mobile = new Mobile();
    /* Save Request - Start */
    // $reqResId = $mobile->saveMobileRequest($data,"cartPara",$app_version,$device);
    /* Save Request - End */
    $cart_items = [];
    if(isset($data['cart']) && !empty($data['cart'])){
      if(isset($data['area_id']) && !empty($data['area_id'])){
        $check = Location::with('parent')->find($data['area_id']);
        $area = $check->status;
        $city = $check->parent->status;
        $store_id = $check->store_id;
        $route_id = $check->route_id;
      }
      else{
        $check = Address::with('location','location.parent')->find($data['address_id']);
        $area = $check->location->status;
        $city =  $check->location->parent->status;
        $store_id = $check->location->store_id;
        $route_id = $check->location->route_id;
      }
      if($area == 1 && $city == 1){
        $product_id = $data['cart'][0]['dish_id'];
        $check = Product::find($product_id);
        if(!$check){
          return response()->json([
            "Code" => 403,
            "is_valid_order" => 0,
            "Message_en" => "Dear customer, this product does not exist.",
            "Message_ar" => ".عزيزي العميل ، هذا المنتج غير موجود"
          ]);

        }
       

      //   $stock = \App\Model\ProductStock::select('product_id','refill_stock_limit','product_name','stock','reserved_stock')->where('store_id', $store_id)
      //   ->whereRaw('stock - reserved_stock > refill_stock_limit')//reserved stock
      //   ->join('products', 'product_stocks.material', '=', 'products.material')
      //   ->where('products.status',1)
      //   ->get();
      //   // $stock = \App\Model\ProductStock::selectRaw('stock - reserved_stock')->get();

      //   $stock_qty = $stock;
      //   $stock = $stock->pluck('product_id')->toArray();
      //   $stock_qty = $stock_qty->toArray();

      //   foreach ($data['cart'] as $key => $value) {
      //     array_push($cart_items, $value['dish_id']);
      //     if(in_array($value['dish_id'],$stock)){
      //       // continue;
      //     }
      //     else{
      //       $name = \App\Model\Product::where('product_id',$value['dish_id'])->value('product_name');
      //       $prod_name = json_decode($name,true);

      //       return response()->json([
      //         "Code" => 403,
      //         "is_valid_order" => 0,
      //         "Message_en" => "Dear customer, {$prod_name['en']} is not available currently.",
      //         "Message_ar" => "هذا الصنف غير متوفر حاليا {$prod_name['ar']} عميلنا العزيز ."
      //       ]);
      //     }

      //     foreach ($stock_qty as $key1 => $value1) {
      //       if($value['dish_id'] == $value1['product_id']){
      //         if($value['count'] > ($value1['stock']-$value1['reserved_stock'])){
      //           $prod_name = json_decode($value1['product_name'],true);
      //           $diff = $value['count'] - ($value1['stock']-$value1['reserved_stock']);
      //           $st = ($value1['stock']-$value1['reserved_stock']);
      //           return response()->json([
      //             "Code" => 403,
      //             "is_valid_order" => 0,
      //             "Message_en" => "We only have {$st} {$prod_name['en']} left please remove {$diff} to proceed.",
      //             "Message_ar" => "لم يتبق لدينا سوى {$st} {$prod_name['ar']} يرجى إزالة {$diff} للمتابعة"
      //           ]);
      //         }
      //       }
      //     }

      //   }
      }
      else{
        return response()->json([
          "Code" => 403,
          "is_valid_order" => 0,
          "Message_en" => "Dear Customer, we apologize for this area outside the coverage currently.",
          "Message_ar" => "عميلنا العزيز نعتذر منك المنطقه خارج التغطيه حالياً."
        ]);
      }
    }

    if(isset($data['fcmToken']) && !empty($data['fcmToken'])){
      if($device == 'HMS'){
        $data['fcmToken'] = '_HMS_'.$data['fcmToken'];
      }
      $cus = CustomerExtra::updateOrCreate(
        ['customer_id' => $data['user_id']],
        ['customer_id' => $data['user_id'],'fcm_token' => $data['fcmToken']]
      );
    }


    /*$optionResult = new GetOption();
    $deleverySlots = $optionResult->GetDeliverySlots();
    $getDileverySlots = array();

    for($i=0; $i < count($deleverySlots) ; $i++){
    $title = json_decode($deleverySlots[$i]['title']);
    array_push($getDileverySlots, array(
    "id" => "".$deleverySlots[$i]['id'],
    "title_en" => $title-> en,
    "title_ar" => $title-> ar,
    "status" =>  "".$deleverySlots[$i]['status'],

  ));
}*/

$model = new DeliverySlot();

$show_today_date = \App\Model\Option::getValueByKey('SHOW_TODAY_DATE');
$days_count = \App\Model\Option::getValueByKey('DELIVERY_SLOT_DAYS');
if(isset($show_today_date) && $show_today_date == 1){
  $first = date('Y-m-d H:i:s');
}else{
  $first = date("Y-m-d H:i:s", strtotime("tomorrow"));
}

$customer = Customer::find($data['user_id']);
$days = "+".$days_count." day";
$last = date('Y-m-d H:i:s');
$last = strtotime($last);
$last = strtotime($days, $last);
$last = date('Y-m-d H:i:s', $last);
$route_id = isset($route_id)?$route_id:1;
$delivery_channel = isset($customer->channel_id) ? $customer->channel_id: 0;



if($customer['account_type_id'] != 0){
  $check_corporate = Option::getValueByKey('SHOW_CORPORATE_DELIVERY_SLOT');
  // $check_corporate = 0;
  if($check_corporate != 1){
    $get_dilevery_slots = array();
    $get_dilevery_slots['days'] = '';
    $get_dilevery_slots['time'] = '';
    // dd($get_dilevery_slots);
  }
  else{
    $model_customer = new CustomerDeliverySlot();
    if($customer['parent_id'] == null){
      $customer_delivery_id = $customer['customer_id'];
    }else{
      $customer_delivery_id = $customer['parent_id'];
    }
    $get_dilevery_slots =  $model_customer->getDeliveryData($first,$last,'+1 day','D, d M y',1,$delivery_channel,$customer_delivery_id,$days_count);
    if(count($get_dilevery_slots) > $days_count){
      array_pop($get_dilevery_slots);
    }
  }
}
else{
    $get_dilevery_slots = $model->getSlots($first,$last,'+1 day','D, d M y',1,$delivery_channel,$days_count); // ,$route_id
    if(count($get_dilevery_slots) > $days_count){
      array_pop($get_dilevery_slots);
    }
}


$getOrderCode = GetOrder::GetDuplicateOrders($data['user_id']);

$getLast3HourOrder = GetOrder::GetLast3HourOrder($data['user_id']);

/* Get last 3 hour Orders - start */
if($getLast3HourOrder){
  for($i=0; $i < count($getLast3HourOrder); $i++){
    $this->Last3HourOrders[$i]['address_id'] = $getLast3HourOrder[$i]['shipping_address_id'];
    for($a=0; $a < count($getLast3HourOrder[$i]['items']); $a++){  //print_r($getLast3HourOrder);exit;
      $this->Last3HourOrders[$i]['order_items'][$a]['product_id'] =  $getLast3HourOrder[$i]['items'][$a]['product_id'];
      $this->Last3HourOrders[$i]['order_items'][$a]['qty'] =  $getLast3HourOrder[$i]['items'][$a]['quantity'];
    }
  }
}
/* Get last 3 hour Orders - end */


$wallet_amount = 0;
$wallet_discount = 0;
$show_stc_tamayouz=0;

$show_stc_tamayouz = Option::where('option_key', 'SHOW_STC_TAMAYOUZ')->get()->toArray();
if(isset($show_stc_tamayouz[0]['option_value'])){
      $show_stc_tamayouz = $show_stc_tamayouz[0]['option_value'];
}else {
  $show_stc_tamayouz=0;
}

$stc_tamayouz_min_qty = 15;
if($customer->account_type_id == 0){
  $current_balance = 0;

  $mobile = new Mobile();
  $mobile = $mobile->getDeviceType();

  $getPaymentsMethods = Option::where('option_group','Payment Methods')->where('option_key','!=','CUSTOMER_CREDIT')
  ->where('option_value',1);

  if($mobile == 9){
    $getPaymentsMethods = $getPaymentsMethods->where('option_meta->android_sort', '>', 0)->orderBy('option_meta->android_sort', 'asc')->get()->toArray();
  }
  else{
    $getPaymentsMethods = $getPaymentsMethods->where('option_meta->ios_sort', '>', 0)->orderBy('option_meta->ios_sort', 'asc')->get()->toArray();
  }

  for($i=0; $i < count($getPaymentsMethods); $i++){

    $title = json_decode($getPaymentsMethods[$i]['option_name']);
    $paymentMethodId = json_decode($getPaymentsMethods[$i]['option_meta']);
    $paymentsMethods[$i]['id'] = $paymentMethodId->payment_method_id;
    $paymentsMethods[$i]['title_en'] = $title->en;
    if($paymentMethodId->payment_method_id == 15){
      $paymentsMethods[$i]['title_ar'] = ($mobile == 9)?$title->ar:'الدفع بالبطاقة عند الاستلام';
    }else{
      $paymentsMethods[$i]['title_ar'] = $title->ar;
    }
    $paymentsMethods[$i]['status'] = $getPaymentsMethods[$i]['option_value'];

  }

  $mod = new WalletTransaction();
  $wallet_amount = $mod->update_wallet($data['user_id']);
  $wallet_discount = $mod->get_discount($data['user_id']);
  if($wallet_amount < 0){
    $wallet_amount = 0;
  }
  if($wallet_discount < 0){
    $wallet_discount = 0;
  }
}
else{
  $show_stc_tamayouz = 0;
  $stc_tamayouz_min_qty = 0;
  if($customer->account_type_id != 2){
    // $model = new \Modules\Services\Http\Controllers\Erp\Internal\ErpCustomerController();
    // $model->updateCustomerCreditById($customer->erp_id);
    updateCustomerCredit($customer->customer_id); //helper function
    $customer->refresh();
    $current_balance = $customer->current_balance;
  }
  else{
    $parent = Customer::find($customer->parent_id);
    // $model = new \Modules\Services\Http\Controllers\Erp\Internal\ErpCustomerController();
    // $model->updateCustomerCreditById($parent->erp_id);
    updateCustomerCredit($parent->customer_id); //helper function
    $parent->refresh();
    $current_balance = $parent->current_balance;
  }
  $getPaymentsMethods = Option::where('option_key','CUSTOMER_CREDIT')->get()->first();

  $title = json_decode($getPaymentsMethods->option_name);
  $paymentMethodId = json_decode($getPaymentsMethods->option_meta);
  $paymentsMethods[0]['id'] = $paymentMethodId->payment_method_id;
  $paymentsMethods[0]['title_en'] = $title->en;
  $paymentsMethods[0]['title_ar'] = $title->ar;
  $paymentsMethods[0]['status'] = $getPaymentsMethods->option_value;
}

if(!empty($getOrderCode))
{
  $getOrderCode = $getOrderCode->toArray();
  $orders = "";
  for($i=0; $i < count($getOrderCode) ; $i++){
    $orders .=  $getOrderCode[$i]['order_number'].",";
  }

  $getOrderCode = rtrim($orders, ',');

}
else{
  $getOrderCode = "";
}

$promo_object = '';
$default_promo = '';

$default_promo = ExtCompContract::with(array('company.promocode'=>function($query){
  $query->where('promocodes.start_date', '<=', date('Y-m-d H:i:s'));
  $query->where('promocodes.end_date', '>=', date('Y-m-d H:i:s'));
  $query->where('promocodes.status', 1);
  }))
  ->where('customer_id',$data['user_id'])
  ->where('status',1)
  ->get()->first();

  if(isset($default_promo->company->promocode) && $default_promo->company->promocode != Null){
    $default_promo = $default_promo->company->promocode;
  }else{
    $default_promo = Null;
  }

  if(!$default_promo){
    $default_promo = Promocode::where('is_default',1)
    ->where('start_date', '<=', date('Y-m-d H:i:s'))
    ->where('end_date', '>=', date('Y-m-d H:i:s'))
    ->where('status', 1)->get()->first();
  }

  if($default_promo){
    $data['promo_code'] = $default_promo->promo_code_access;
    $data['cart'] = $data['cart'];
    $data['client_id'] = $data['user_id'];
    $promo = new CheckPromocode();
    $validate = $promo->isValid($data);
    if($validate['is_promo_valid'] == 1){
      $promo_object = $validate;
    }
    else{
      $promo_object = '';
      $default_promo = '';
    }
  }


  $delivery_fee = \App\Model\Option::getValueByKey('DELIVERY_FEE');

  if(isset($check->location->parent->delivery_fee)){
    $delivery_fee = $check->location->parent->delivery_fee;
  }elseif(isset($check->parent->delivery_fee)) {
    $delivery_fee = $check->parent->delivery_fee;
  }

  $show_order_note = \App\Model\Option::getValueByKey('SHOW_ORDER_NOTE');
  $show_order_note = (!empty($show_order_note) ? $show_order_note : 0) ;

  $show_drop_and_go = \App\Model\Option::getValueByKey('SHOW_DROP_AT_GATE');
  $show_drop_and_go = (!empty($show_drop_and_go) ? $show_drop_and_go : 0) ;

  $show_house_no = \App\Model\Option::getValueByKey('SHOW_BUILDING_INPUT');
  $show_house_no = (!empty($show_house_no) ? $show_house_no : 0) ;

  $customer_payment = \App\Model\CustomerPaymentReg::select('id','card_no','payment_type','brand')
  ->where('customer_id',$data['user_id'])
  ->where('status',1)
  ->get()->toArray();


  foreach($customer_payment as &$item)
  {
    $item['card_no'] = substr($item['card_no'], -4);
    $item['brand'] = isset($item['brand'])?$item['brand']:'';
  }


    $all_similar_products = SimilarProduct::get()->toArray();
    $similar_products = [];
    $count = 0;

    foreach ($all_similar_products as $key => $value) {
      if (!in_array($value['product_id'],$cart_items) && $count < 4) {
        array_push($similar_products,$value['product_id']);
        $count++;
      }
    }


  return response()->json([
    "Code" => 200,
    "is_valid_order" => 1,
    "delivery_slots" => $get_dilevery_slots
  ]);

}






public function NewAddressWithOrder($addressData,$user_id,$source = 0,$app_version="2.1",$device=Null,$floor_no='',$house_no='')
{
  $mobile = new Mobile();

  /* Temp Order - Start */
  $addressData['user_id'] = $user_id;
  if($source > 0){
    $addressData['order_source_id'] = $source;
  }
  // $reqResId = $mobile->saveMobileRequest($addressData,"NewAddressWithOrder",$app_version,$device);
  /* Temp Order - End */
  $addressData = $mobile->clean_sqlwords($addressData);
  $user_id = $mobile->clean_sqlwords($user_id);

  $param = ["customer_id" => $user_id,
  "location_id" => $addressData['add_area'],
  "title" => $addressData['add_name'],
  "address" => $addressData['add_detail'],
  "type" => isset($addressData['add_type'])?$addressData['add_type']:1,
  "map_info" => array("latitude" => $addressData['add_latitude'],"longitude" => $addressData['add_longitude']),
  "source_id" => ($source > 0) ? $source : $mobile->getDeviceType(),
  "status" => 1
];

if($floor_no != null && $floor_no != ''){
  $param['floor_no']=$floor_no;
}
if($house_no != null && $house_no != ''){
  $param['floor_no']=$house_no;
}

$address = new Address();
$newAddress = $address->NewAddress($param);
if(isset($newAddress->address_id) && !empty($newAddress->address_id)){
  if(!empty($reqResId)){
    $mobile->updateReqRespone($reqResId);
  }
  return $newAddress->address_id;
}

}


}
