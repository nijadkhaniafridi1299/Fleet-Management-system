<?php

namespace App\Model;
use App\Order as AppOrder;
use App\Message\Error;
use App\Model\Product;
use App\Model\Cart;
use App\Model\CartItem;
use App\Model\Option;
use App\Model\Promocode;
use App\Model\CartService;
use App\Model\MobileOrder as OrderService;

class SaveOrder extends AppOrder{

  protected $errors = [];
  protected $getOrderId = 0;
  protected $promocode_id = 0;

  function create($param){
   
    $order_value = 0; $order_qty = 0; $discount = 0;
    $cart = new CartService($param['user_id']);
    
    if(!empty($cart)){
      $cart_id = $cart->toArray();
      $cart_id = $cart_id['cart_id'];
    } else{
      $cart_id['cart_id'] = "";
    }

    $order = new OrderService();

    $customer = \App\Model\Customer::find($param['user_id']);

    $address = \App\Model\Address::onWriteConnection()->find($param['address_id']);
    
    for($i=0; $i < count($param['cart']); $i++){
        $param['cart'][$i]['productVariantId'] = null;
      
      // if($param['cart'][$i]['productVariantId'] == null || $param['cart'][$i]['productVariantId'] == '' || $param['cart'][$i]['productVariantId'] == 0){
        $prodPrice = getProduct($param['user_id'],$param['cart'][$i]['dish_id'],$address->type); //helper function
      // }else {
        // $prodPrice = getVariant($param['user_id'],$param['cart'][$i]['dish_id'],$address->type,$param['cart'][$i]['productVariantId']); //helper function
      // }
      if(!is_object($prodPrice)){
        unset($param['cart'][$i]);
      }
      else{
        $param['cart'][$i]['price'] = $prodPrice->price;
        $order_value += ($prodPrice->price * $param['cart'][$i]['count']) ;
        $order_qty += $param['cart'][$i]['count'];
        $param['cart'][$i]['parice'] = 0;
      }
    }
    $param['cart'] =array_values($param['cart']);
    // if(empty($param['cart'])){
    //   return ['order_id' => '', "grand_total" => '',  'error' => 'Product not available'];
    // }
    // dd($param);
    $count = count($param['cart']);
    for($i=0 ; $i < $count; $i++){
      $cart->addProduct($param['cart'][$i]['dish_id'], $param['cart'][$i]['count'],0,$param['cart'][$i]['price'],$param['address_id']);
      
    }
    // dd($cart->toArray());
    // exit;
    //Check promo discount value
    $stc = ($param['stc_otp'] == 1)?'stc':'';

    if($param['promocode'] != "")
    {
      if($address->type == 2){
        $channel = \App\Model\Channel::where('channel_code', 'Mosque')->get()->first();
        $sub_channel_id = $channel->channel_id;
      }
      else{
        if($customer->account_type_id == 2){
          $customer = \App\Model\Customer::find($customer->parent_id);
        }
        $sub_channel_id = $customer->sub_channel_id;
      }
      $promo =  $cart->applyPromoCode($param['promocode'],0,$sub_channel_id,$stc,$customer->customer_id);
      if(!$promo)
      {
        // $this->errors = Error::get('promocode.apply');
      }
      else{
        $getPromocoe = Promocode::where('promo_code_access',$param['promocode'])
        ->first();
        if(isset($getPromocoe['promocode_id'])){
          $this->promocode_id = $getPromocoe['promocode_id'];
        }

      }
    }
    $getCart = Cart::onWriteConnection()->where("cart_id", $cart_id)->first();
    if(!empty($getCart)){
      $getCart = $getCart->toArray();
    }
    // dd($getCart);
    $discount = $getCart['discount'];
    $grand_total = $order_value - $discount;

    $vat = Option::getValueByKey('VAT_IN_PERCENT'); // Get VAT
    $vat = round( $vat * $grand_total / 100, 2);

    $grand_total = $grand_total+$vat;
    if($customer['account_type_id'] != 0){
      // if($customer['account_type_id'] != 2){
      //   if($customer['current_balance'] < $grand_total){
      //     $this->errors = 'Total amount greater than credit limit';
      //     return ['error' => $this->errors];
      //   }
      // }
      // else{
      //   $parent = \App\Model\Customer::find($customer['parent_id']);
      //   if($parent->current_balance < $grand_total){
      //     $this->errors = 'Total amount greater than credit limit';
      //   }
      // }
      $param['payment_method'] = 'CUSTOMER_CREDIT';
    }
    // dd($param);
    $post = ["vat" => $vat,
    "grand_total" => $grand_total,
    "address_id" => $param['address_id'],
    "aqg_loc_id" => $param['aqg_loc_id'],
    // "weight" => $param['weight'],
    "customer_dropoff_loc_id" => $param['customer_dropoff_loc_id'],
    // "prefered_time" => $param['prefered_time'],
    // "prefered_day" => $param['prefered_day'],
    "payment_method" => $param['payment_method'],
    "promocode_id" => $this->promocode_id,
    "payment_id" => $param['payment_id'],
    "order_source" => $param['order_source'],
    "is_wallet" => $param['is_wallet'],
    "department_name" => $param['department_name'],
    "dpc" => $param['dpc'],
    "contact_person" => $param['contact_person'],
    "phone" => $param['phone'],
    "log_in_id" => $param['log_in_id'],
    "disposal_type" => $param['disposal_type'],
    "net_weight" => $param['net_weight'],
    "no_of_vehicles" => $param['no_of_vehicles'],
    "unit" => $param['unit'],
    // "material_types" => $param['material_types'],
    "contract_work_permit" => $param['contract_work_permit'],
    "required_start_date" => $param['required_start_date'],
    "estimated_end_date" => $param['estimated_end_date'],
    "is_segregation_required" => $param['is_segregation_required'],
    "is_collection_required" => $param['is_collection_required'],
    "comments" => $param['comments'],
    "category_id" => $param['category_id'],
    "category_key" => $param['category_key'],
    "shipping_address_id" => $param['shipping_address_id'],
    "site_location" => $param['site_location'],
    "created_by" => $param['created_by'],
    "customer_lot_id" => $param['customer_lot_id']
  ];
  $this->getOrderId =  parent::place($cart,$post);

  return ['order_id' => $this->getOrderId, "grand_total" => $grand_total,  'error' => $this->errors];

}

// function create_old($param){
//   $order_value = 0; $order_qty = 0; $discount = 0;
//   $cart = new \Modules\Services\Cart($param['user_id']);
//   if(!empty($cart)){
//     $cart_id = $cart->toArray();
//     $cart_id = $cart_id['cart_id'];
//   } else{
//     $cart_id['cart_id'] = "";
//   }

//   if($param['promocode'] != "")
//   {
//     $promo =  $cart->applyPromoCode($param['promocode']);
//     if(!$promo)
//     {
//       $this->errors = Error::get('promocode.apply'); //print_r($errors);exit;
//     }
//     else{
//       $getPromocoe = Promocode::where('promo_code_access',$param['promocode'])
//       ->where('status',1)
//       ->first();
//       if(isset($getPromocoe['promocode_id'])){
//         $this->promocode_id = $getPromocoe['promocode_id'];
//       }

//     }
//   }

//   $order = new OrderService();

//   $customer = \App\Model\Customer::find($param['user_id']);

//   // dd($param['cart']);
//   for($i=0; $i < count($param['cart']); $i++){
//     // dd($param['user_id'],$param['cart'][$i]['dish_id']);
//     $prodPrice = getProduct($param['user_id'],$param['cart'][$i]['dish_id']); //helper function
//     // $prodPrice = Product::where("product_id", $param['cart'][$i]['dish_id'])->where("status","=",1)->first()->toArray();
//     //   print_r($param['cart'][$i]['dish_id']);
//     //   echo "<br>";
//     if(!is_object($prodPrice)){
//       unset($param['cart'][$i]);
//     }
//     else{
//       $param['cart'][$i]['price'] = $prodPrice->price;
//       $order_value += ($prodPrice->price * $param['cart'][$i]['count']) ;
//       $order_qty += $param['cart'][$i]['count'];
//       $param['cart'][$i]['parice'] = 0;
//     }
//   }
//   // dd($param);
//   $count = count($param['cart']);
//   for($i=0 ; $i < $count; $i++){
//     $cart->addProduct($param['cart'][$i]['dish_id'], $param['cart'][$i]['count'],0,$param['address_id']);
//   }
//   //Check promo discount value
//   $getCart = Cart::where("cart_id", $cart_id)->first();
//   if(!empty($getCart)){
//     $getCart = $getCart->toArray();
//   }

//   $discount = $getCart['discount'];
//   $grand_total = $order_value - $discount;

//   $vat = Option::getValueByKey('VAT_IN_PERCENT'); // Get VAT
//   $vat = round( $vat * $grand_total / 100, 2);

//   $grand_total = $grand_total+$vat;
//   if($customer['account_type_id'] != 0){
//     if($customer['account_type_id'] != 2){
//       if($customer['current_balance'] < $grand_total){
//         $this->errors = 'Total amount greater than credit limit';
//         return ['error' => $this->errors];
//       }
//     }
//     else{
//       $parent = \App\Model\Customer::find($customer['parent_id']);
//       if($parent->current_balance < $grand_total){
//         $this->errors = 'Total amount greater than credit limit';
//       }
//     }
//   }

//   if($customer['account_type_id'] != 0){
//     $param['payment_method'] = 'CUSTOMER_CREDIT';
//   }

//   $post = ["vat" => $vat,
//   "grand_total" => $grand_total,
//   "address_id" => $param['address_id'],
//   "prefered_time" => $param['prefered_time'],
//   "payment_method" => $param['payment_method'],
//   "promocode_id" => $this->promocode_id,
//   "order_source" => $param['order_source']
// ];

// $this->getOrderId =  parent::place($cart,$post);

// return ['order_id' => $this->getOrderId, "grand_total" => $grand_total,  'error' => $this->errors];

// }
}
