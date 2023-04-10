<?php

namespace App;
use App\Model\Order as CustomerOrder ;
use App\Model\OrderItem as CustomerOrderItem ;
use Modules\Services\Authenticate\Mobile;
use App\Model\Cart as CustomerCart;
use App\Model\CartItem;
use App\Message\Error;
use App\Model\Customer;
use App\Model\ProductStock;
use App\Model\Product;
use App\Model\Address;
use App\Model\Channel;
use Carbon;
use App\Mail\DemoEmail;
use Illuminate\Support\Facades\Mail;
use App\Model\WalletTransaction ;
use App\Payment\HyperPay;
use App\Model\ExtCompContract;
use App\Model\GenesysOrder;
use App\Model\ProductLog;
use App\Model\OrderLog;
use App\Model\ProductCustomerLog;
use Auth;
class Order{

  protected $vat = 15;

  function __construct(){
    $this->vat = \App\Model\Option::getValueByKey('VAT_IN_PERCENT');
    $this->delivery_fee = \App\Model\Option::getValueByKey('DELIVERY_FEE');
  }

  function place(Cart $cart, $post){
    

    $cartInfo = $cart->toArray();
    
        $customer_id = \App\Model\Customer::getLoggedInCustomer();

  if($customer_id){
    $customer_id = $customer_id['customer_id'];
  }
  else{
    $customer_id= null;}
  $ip = getIp();


  foreach ($cartInfo['items'] as $item){
  $stock = ProductStock::where('material',$item['product']['material'])->increment('reserved_stock' , $item['quantity']);

  $logs = ProductLog::whereProductId($item['product_id'])->first();
  
    if(!$customer_id){
      $customer_id= null;
    }
      $customer_log = new ProductCustomerLog;
      $customer_log->product_id = $item['product_id'];
      $customer_log->customer_id = $customer_id;
      $customer_log->function = "order_placed";
      $customer_log->ip = $ip;
      $customer_log->save();


    if($logs){
      $logs->increment('order_placed');
    }
    else{
      $logs = new ProductLog();
      $logs->product_id = $item['product_id'];
      $logs->order_placed = 1;
      $logs->save();
    }
   }
   
  // return $order_number;
    $cart_id = $cartInfo['cart_id'];
    //echo '<pre>'.print_r($cart->toArray(), true).print_r($_POST, true).'</pre>';
    // $customer = session('customer');
    $customer['customer_id'] = Auth::id();
   
    if($cartInfo['customer_id']){
      $customer_id = $cartInfo['customer_id'];
    }
    else {
      $customer_id = $customer['customer_id'];
    }

    if($cartInfo['total'] == 0){
      $cartInfo['total'] = $cartInfo['item_total'];
    }

    $delivery = Address::with('location.parent')->find($post['address_id']);

    $delivery_formula = \App\Model\Option::select('option_id','option_meta')->where('option_key','DELIVERY_FEE')->first();
    $delivery_formula = json_decode($delivery_formula->option_meta,true);

    if ($delivery_formula['value'] == 'item') {
      $this->delivery_fee=$this->delivery_fee * $itemCount;
    }elseif ($delivery_formula['value'] == 'weight') {
      $weight = 0;
      foreach ($cartInfo['items'] as $key => $value) {
        $weight += isset($value['product']['weight'])?$value['product']['weight']:1;
      }
      $this->delivery_fee=$this->delivery_fee * $itemCount * $weight;
    }



    $add_wallet = 0;
    $new_discount = 0;

    if($cartInfo['promocode_type'] == 'after_vat'){

      $grand_total = $cartInfo['total'];
      $vat = round($this->vat * ($grand_total+$this->delivery_fee) / 100, 2);
      $grand_total = ($grand_total+$vat+$this->delivery_fee-$cartInfo['discount']);
      $add_wallet = ($grand_total > 0)?$cartInfo['discount']:abs($grand_total);
      $add_wallet = ($cartInfo['discount'] == $add_wallet)?0:$add_wallet;
      $cartInfo['discount'] = (($cartInfo['discount']-$add_wallet) > 0)?($cartInfo['discount']-$add_wallet):$cartInfo['discount'];
      $grand_total = ($grand_total > 0)?$grand_total:0;
    }else{
      $grand_total = $cartInfo['total'] + $this->delivery_fee - $cartInfo['discount'];
      $vat = round($this->vat * $grand_total / 100, 2);
      $grand_total += $vat;
    }

    $wallet_amount = 0;
    $amount_to_deduct = 0;
    $wallet_discount = 0;
    $grand_total_d = $grand_total;

    //
    $customer_c = \App\Model\Customer::find($customer_id);

   

    $default_order_status_option = \App\Model\Option::getValueByKey('DEFAULT_ORDER_STATUS');
    $default_order_status = 5;


    switch($default_order_status_option) {
      case 'confirmed':
        $default_order_status = 2;
        break;
      case 'placed':
        $default_order_status = 5;
        break;
      case 'ready_for_pick_up':
          $default_order_status = 14;
          break;
      case 'first_placed_later_confirmed':
        $any_delivered_order = CustomerOrder::where('customer_id', $customer_id)->where('order_status_id', 4)->first();
    }

    // If Order is of Skip Collection, order_status should be of "waiting for acceptance"
    if($post['category_key'] == "SKIP_COLLECTION" || $post['category_key'] == "ASSET" || (isset($post['customer_lot_id']) && $post['customer_lot_id'] != null)){
      $default_order_status = 15;
    }
    $key = Customer::where('customer_id',$customer_id)->value('key');
    if($key == "AQG"){
      $default_order_status = 16;
    }

    $payment_method = isset($post['payment_method']) ? $post['payment_method'] : "CASH_ON_DELIVERY";
    $payment_id = isset($post['payment_id']) ? $post['payment_id'] : Null;
    $order_status_id = isset($post['order_status_id']) ? $post['order_status_id'] : $default_order_status;
    $source_id = isset($post['order_source']) ? $post['order_source'] : 1;

    if($grand_total <= 0){
      $payment_method = 'WALLET';
    }

    try{

      $order_data = [
        "customer_id" => $customer_id,
        "shipping_address_id" => $post['shipping_address_id'],
        "site_location" => $post['site_location'],
        "created_by" => $post['created_by'],
        "pickup_address_id" => $post['address_id'],
        "aqg_dropoff_loc_id" => $post['aqg_loc_id'],
        "net_weight" => $post['net_weight'],
        "unit" => $post['unit'],
        "customer_dropoff_loc_id" => $post['customer_dropoff_loc_id'],
        "payment_method" => $payment_method,
        "payment_id" => $payment_id,
        "total" => $cartInfo['total'],
        "discount" => $cartInfo['discount'],
        "order_status" => "Placed",
        "source_id" => $source_id,
        "vat" => $vat,
        "grand_total" => $grand_total,
        "order_status_id" => $order_status_id,
        "order_number" => CustomerOrder::createOrderNumber($customer_id),
        "wallet_amount" => $wallet_amount,
        "wallet_discount" => $wallet_discount,
        "drop_at_gate" => isset($post['drop_at_gate']) ? $post['drop_at_gate'] : 0,
        "order_note" => isset($post['order_note']) ? $post['order_note'] : '',
        "order_pin" => mt_rand(1000, 9999),
        "delivery_fee" => $this->delivery_fee,
        "dpc" => $post['dpc'],
        "department_name" => $post['department_name'],
        "contact_person" => $post['contact_person'],
        "phone" => $post['phone'],
        "contact_person" => $post['contact_person'],
        "log_in_id" => $post['log_in_id'],
        "disposal_type" => $post['disposal_type'],
        "required_vehicles" => $post['no_of_vehicles'],
        "contract_work_permit" => $post['contract_work_permit'],
        "required_start_date" => $post['required_start_date'],
        "estimated_end_date" => $post['estimated_end_date'],
        "is_segregation_required" => $post['is_segregation_required'],
        "is_collection_required" => $post['is_collection_required'],
        "category_id" => $post['category_id'],
        "comments" => $post['comments'],
        "created_at" => date('Y-m-d H:i:s'),
        "updated_at" => date('Y-m-d H:i:s'),
        "customer_lot_id" =>  $post['customer_lot_id']
       
      ];
      
      $last_order = CustomerOrder::where('customer_id', $customer_id)->where('order_status_id', '!=', 6)->orderBy('created_at', 'desc')->first();

      if($last_order){ // Cannot place Order if last placed order is less than 5 minutes
        $date =  Date('Y-m-d H:i:s');
        $start = strtotime($last_order->created_at);
        $end = strtotime($date);
        $check = (($end-$start)/60);

        if($check <= 2){
          if($last_order->customer_id == $order_data['customer_id']
          // && $last_order->shipping_address_id == $order_data['shipping_address_id']
          && $last_order->pickup_address_id == $order_data['pickup_address_id']
          // && $last_order->customer_dropoff_loc_id == $order_data['customer_dropoff_loc_id']
          // && $last_order->required_start_date == $order_data['required_start_date']
          // && $last_order->estimated_end_date == $order_data['estimated_end_date']
         
        ) {
          return $last_order->order_id;
        }
      }
    }
    
    $order = CustomerOrder::create($order_data); //Order Placement
    
    // $order->delivery_time = $prefered_time;
    //
    // $order->save();

    $customer = \App\Model\Customer::find($customer_id);
    if($customer->account_type_id != 0){
      if($customer->account_type_id == 2){
        $parent = \App\Model\Customer::find($customer->parent_id);
        $parent->current_balance -= $order->grand_total;
        $parent->save();
      }
      else{
        $customer->current_balance -= $order->grand_total;
        $customer->save();
      }
    }
    $log['order_id'] = $order->order_id;
    $log['order_status_id'] = $default_order_status;
    $log['source_id'] = $source_id;
    // $log['user_id'] = ($source_id == 8)?0:$customer_id;
    $log['user_id'] = $customer_id;
    $orderLog =  new \App\Model\OrderLog();
    $orderLog->add($log);
    if($post['category_key'] == "SKIP_COLLECTION" || $post['category_key'] == "ASSET"){
        $log['order_id'] = $order->order_id;
        $log['order_status_id'] = 5;
        $log['source_id'] = $source_id;
        // $log['user_id'] = ($source_id == 8)?0:$customer_id;
        $log['user_id'] = $customer_id;
        $orderLog->add($log);

        $log['order_id'] = $order->order_id;
        $log['order_status_id'] = 15;
        $log['source_id'] = $source_id;
        // $log['user_id'] = ($source_id == 8)?0:$customer_id;
        $log['user_id'] = $customer_id;
        $orderLog->add($log);


    }
    $key = Customer::where('customer_id',$customer_id)->value('key');
    if($key == "AQG"){
      $log['order_id'] = $order->order_id;
      $log['order_status_id'] = 5;
      $log['source_id'] = $source_id;
      // $log['user_id'] = ($source_id == 8)?0:$customer_id;
      $log['user_id'] = $customer_id;
      $orderLog->add($log);

      $log['order_id'] = $order->order_id;
      $log['order_status_id'] = 15;
      $log['source_id'] = $source_id;
      // $log['user_id'] = ($source_id == 8)?0:$customer_id;
      $log['user_id'] = $customer_id;
      $orderLog->add($log);
    }

    if(isset($post['customer_lot_id']) && $post['customer_lot_id'] != null){
      $orderlogsdata[] = [

        'order_id' => $order->order_id,
        'order_status_id' => 5,
        'source_id' => 12,
        'user_id' => $customer_id,
        'created_at' =>  date('Y-m-d H:i:s'),
        'updated_at' =>  date('Y-m-d H:i:s')
      
      
      ];
      
      \App\Model\OrderLog::insert($orderlogsdata);
    }

    //send notification to customer
    //Ayesha: 26/11/2020

  }
  catch(\Exception $ex){
    echo $ex->getMessage(); exit;
  }

  $order_id = $order->order_id;

  // $address = Address::find($post['address_id']);
  //
  // if($address->type == 2){
  //   $channel = Channel::where('channel_code', 'Mosque')->get()->first();
  //   $promotions = \App\Model\Promotion::where('status',1)
  //   ->where('start_date','<=',date('Y-m-d H:i:s'))
  //   ->where('end_date','>=',date('Y-m-d H:i:s'))
  //   ->where('channel_id',$channel->channel_id)
  //   ->get()
  //   ->toArray();
  // }
  // else{

  $company_promotion = Null;
  $company_promotion = ExtCompContract::with(array('company.promotion' => function ($query) {
    $query->where('promotions.start_date', '<=', date('Y-m-d H:i:s'));
    $query->where('promotions.end_date', '>=', date('Y-m-d H:i:s'));
    $query->where('promotions.status', 3);
    }))
    ->where('customer_id', $customer['customer_id'])
    ->get()->first();

    if (isset($company_promotion->company->promotion) && $company_promotion->company->promotion != Null) {
      $company_promotion = $company_promotion->company->promotion->toArray();
    } else {
      $company_promotion = Null;
    }

    $promotions = \App\Model\Promotion::where('status',1)
    ->where('start_date','<=',date('Y-m-d H:i:s'))
    ->where('end_date','>=',date('Y-m-d H:i:s'))
    ->where('group_id',$customer['group_id'])
    ->where('channel_id',$customer['channel_id'])
    ->get()
    ->toArray();

    if($company_promotion != Null && $company_promotion != ''){
      array_push($promotions, $company_promotion);
    }

    // }
    $isPromotion = False;
    if (count($promotions) > 0) {
      $isPromotion = True;
    }

    for($i=0, $count = count($promotions); $i < $count; $i++){
      $promotions[$i]['locations'] = json_decode($promotions[$i]['locations']);
      $promotions[$i]['on_products'] = json_decode($promotions[$i]['on_products']);
      // $promotions[$i]['gift_products'] = json_decode($promotions[$i]['gift_products']);
      if($promotions[$i]['range_limit'] != Null){
        $promotions[$i]['range_limit'] = json_decode($promotions[$i]['range_limit']);
      }

      //Ayesha 3-6-2021, Adding variants
      // $promotions[$i]['on_variants'] = json_decode($promotions[$i]['on_variants']);
      // $promotions[$i]['gift_variants'] = json_decode($promotions[$i]['gift_variants']);
    }

    for($i=0, $count = count($cartInfo['items']); $i < $count; $i++){
      $cartInfo['items'][$i]['check'] = 0;
      foreach ($promotions as $key => $value) {
        if ($value['promotion_type'] == 'product') {
          if ($value['range_limit'] == Null) {
            if (isset($cartInfo['items'][$i]['check']) && $cartInfo['items'][$i]['check'] != 1){
              if ((in_array($cartInfo['items'][$i]['product_id'], $value['on_products']) || empty($value['on_products'])) && $cartInfo['items'][$i]['quantity'] >= $value['min_quantity']){

                //Ayesha 3-6-2021 Apply variant check if this product has any variants.
                if (isset($cartInfo['items'][$i]['variant']) && count($cartInfo['items'][$i]['variant']) > 0) {
                  // if (in_array($cartInfo['items'][$i]['variant']['variant_id'], $value['on_variants'])) {
                  //   $cartInfo['items'][$i]['foc_items'] = floor(floor($cartInfo['items'][$i]['quantity']/$value['min_quantity'])*$value['add_on']);
                  //   $cartInfo['items'][$i]['check'] = 1;
                  // }else{
                  //   $cartInfo['items'][$i]['check'] = 0;
                  // }
                } else {
                  //if product item does not have any variants than promotion will apply on base quantity.
                  $cartInfo['items'][$i]['foc_items'] = floor(floor($cartInfo['items'][$i]['quantity']/$value['min_quantity'])*$value['add_on']);
                  $cartInfo['items'][$i]['check'] = 1;
                }
              }
              else{
                $cartInfo['items'][$i]['foc_items'] = 0;
                // $cartInfo['items'][$i]['check'] = 1;
              }
            }
          } else {
            if (isset($cartInfo['items'][$i]['check']) && $cartInfo['items'][$i]['check'] != 1){
              if ((in_array($cartInfo['items'][$i]['product_id'], $value['on_products']) || empty($value['on_products']))){
                foreach ($value['range_limit'] as $key1 => $value1) {
                  if (($cartInfo['items'][$i]['quantity'] >= (int)$value1->min) && ($cartInfo['items'][$i]['quantity'] <= (int)$value1->max)){

                    //Ayesha 3-6-2021 apply variant check if product has a variant.
                    if (isset($cartInfo['items'][$i]['variant']) && count($cartInfo['items'][$i]['variant']) > 0) {
                      // if (in_array($cartInfo['items'][$i]['variant']['variant_id'], $value['on_variants'])) {
                      //   $cartInfo['items'][$i]['foc_items'] = $value1->add_on;
                      //   $cartInfo['items'][$i]['check'] = 1;
                      //   break;
                      // }else{
                      //   $cartInfo['items'][$i]['check'] = 0;
                      // }
                    } else {
                      $cartInfo['items'][$i]['foc_items'] = $value1->add_on;
                      $cartInfo['items'][$i]['check'] = 1;
                      break;
                    }
                  }
                  else{
                    $cartInfo['items'][$i]['foc_items'] = 0;
                    // $cartInfo['items'][$i]['check'] = 1;
                  }
                }
              }
            }
          }
        }
      }
    }


    for($i=0, $count = count($cartInfo['items']); $i < $count; $i++){
      /*unset($cartInfo['items'][$i]['cart_item_id'], $cartInfo['items'][$i]['cart_id']);
      $cartInfo['items'][$i]['order_id'] = $order->order_id;
      $data = $cartInfo['items'][$i];
      echo '<pre>'.print_r($data, true).'</pre>'; exit;*/
      if(!$isPromotion){
        $cartInfo['items'][$i]['foc_items'] = 0;
      }
      try{
        $data = [
          "order_id" => $order->order_id,
          "product_id" => $cartInfo['items'][$i]['product_id'],
          "quantity" => $cartInfo['items'][$i]['quantity'],
          "unit_price" => $cartInfo['items'][$i]['unit_price'],
          "foc_items" => $cartInfo['items'][$i]['foc_items'],
          "price" => $cartInfo['items'][$i]['price'],
          // "variant_id" => $cartInfo['items'][$i]['variant_id'],
          "status" => 1
        ];

        CustomerOrderItem::create($data);

      }
      catch(\Exception $ex){
        echo $ex->getMessage(); exit;
      }
    }

    if(isset($post['is_wallet']) && $post['is_wallet'] == 1){
      $mod = new WalletTransaction();
      if ($wallet_amount > 0) {
        $wallet = [
          'type' => 1,
          'customer_id' => $customer_id,
          'order_id' => $order_id,
          'amount' => $wallet_amount,
          'available_amount' => 0,
          'reference' => rand(0, strtotime(date('Y-m-d H:i:s'))),
          'reason_id' => Null,
          'mode' => 'debit',
          'added_by' => $source_id,
          'valid_till' => Null,
        ];

        $mod->add($wallet);
      }
      if($wallet_discount > 0){
        $wallet = [
          'type' => 3,
          'customer_id' => $customer_id,
          'order_id' => $order_id,
          'amount' => $wallet_discount,
          'available_amount' => 0,
          'reference' => rand(0, strtotime(date('Y-m-d H:i:s'))),
          'reason_id' => Null,
          'mode' => 'debit',
          'added_by' => $source_id,
          'valid_till' => Null,
        ];

        $mod->add($wallet);
      }
    }

    if ($add_wallet > 0) {
      $mod = new WalletTransaction();
      $wallet = [
        'type' => 1,
        'customer_id' => $customer_id,
        'order_id' => $order_id,
        'amount' => $add_wallet,
        'available_amount' => $add_wallet,
        'reference' => rand(0, strtotime(date('Y-m-d H:i:s'))),
        'reason_id' => Null,
        'mode' => 'credit',
        'added_by' => $source_id,
        'valid_till' => Null,
      ];

      $mod->add($wallet);
    }

    // CustomerCart::where("cart_id", "=", $cart_id)->delete();
    // CartItem::where("cart_id", "=", $cart_id)->delete();
    // session()->forget('cart_id');

    // $result = \App\Model\NotificationTemplate::sendOrderStatusNotifications($order->order_id);
    // if ($result['code'] == 200) {
    //   //notification sent successfully
    // } else if ($result['code'] == 400){
    //   //todo: log error
    // }

    
    return $order_id;

  }

  function getDetails($order_id, $customer_id){

    $order = CustomerOrder::with('payment_method_info','recurring','promocode:promocode_id,type,promo_code_access','order_status:order_status_id,order_status_title')->where('order_id',$order_id)->where('customer_id', $customer_id)->first();
    if(is_object($order)){

      $order = $order->toArray();
      $order['customer'] = \App\Model\Customer::find($order['customer_id']);

      $order_items = CustomerOrderItem::where("order_id", $order['order_id'])->orderBy('product_id','desc')->get()->toArray();
      $items = CustomerOrderItem::where("order_id", $order['order_id'])
      ->join('products', 'order_items.product_id', '=', 'products.product_id')
      // ->leftjoin('variants', 'order_items.variant_id', '=', 'variants.variant_id')
      ->select('products.*','order_items.*',\DB::raw('(select image_path from product_gallery where order_items.product_id  =   product_gallery.product_id and is_default = 1 and status = 1 limit 1) as image_path'))
      ->orderBy('products.product_id','desc')
      ->get()->toArray();

      for($i=0; $i < count($order_items); $i++ ){
        // $items[$i]['variants'] = \App\Model\Variant::where('variant_id', $items[$i]['variant_id'])->where('channel_id',$order['customer']['channel_id'])->get()->toArray();
        $items[$i]['quantity'] = $order_items[$i]['quantity'];
        // $items[$i]['bottles'] = $items[$i]['bottle_quantity'];
        $items[$i]['price'] = $order_items[$i]['unit_price'];
      }
      $order['items'] = $items;
      $order['address'] = \App\Model\Address::find($order['shipping_address_id']);

    }
    else{
      $order = Null;
    }
    return $order;
  }

  function list(array $params = []){
    $query = CustomerOrder::with('items', 'customer');
    if(isset($params['customer_id'])){
      $query->where("customer_id", $params['customer_id']);
    }
    $orders = $query->paginate(15)->toArray();

    // echo '<pre>'.print_r($orders, true). '</pre>'; exit;

    return $orders;
  }

  function changeStatus($orderId, $status = "Cancelled"){
    return CustomerOrder::where("order_id", $orderId)->update(["order_status" => $status]);
  }

  function reorder($order_id){
    $order = CustomerOrder::find($order_id);
    if(is_object($order)){
      $order_data = $order->toArray();
      unset($order_data['order_id']);
      $order_data['status'] = -1;
      $order = CustomerOrder::create($order_data);
    }

    $items = CustomerOrderItem::where("order_id", $order_id)->get();
    //echo '<pre>'.print_r($items, true).'</pre>'; exit;
    foreach($items as $item){

      if(is_object($item)){
        $item_data = $item->toArray();
        //echo '<pre>'.print_r($item_data, true).'</pre>'; exit;
        unset($item_data['order_item_id']);
        $item_data['order_id'] = $order->order_id;
        CustomerOrderItem::create($item_data);
      }
    }

    return $order;
  }

  // function makeFavourite($orderId){
  //   $order = CustomerOrder::find($orderId);

  //   if(is_object($order)){
  //     try{
  //       $order->is_favourite = 1;
  //       return $order->save();
  //     }
  //     catch(\Exception $ex){
  //       Error::trigger('order.make_favourite', ["db"=>$ex->getMessage()]);
  //     }
  //   }
  //   else {
  //     Error::trigger('order.make_favourite', ["order_id"=>"order not found"]);
  //   }
  // }

  // function makeUnfavourite($orderId){
  //   $order = CustomerOrder::find($orderId);

  //   if(is_object($order)){
  //     try{
  //       $order->is_favourite = 0;
  //       return $order->save();
  //     }
  //     catch(\Exception $ex){
  //       Error::trigger('order.make_unfavourite', ["db"=>$ex->getMessage()]);
  //     }
  //   }
  //   else {
  //     Error::trigger('order.make_unfavourite', ["order_id"=>"order not found"]);
  //   }
  // }


  function cancel($orderId, $cancelReasonId, $userID = '', $device = 1,$refund_to=''){

    $order = CustomerOrder::with('payment:payment_id,amount,transaction_key','walletAmountErp:id,order_id,amount','address.location')->find($orderId);

    if(!is_object($order)){
      Error::trigger('order.cancel', ["order_id"=>"order not found"]);
    }
    if($order->order_status_id == 6){
      return $order;
    }
   
    $cancelReason = \App\Model\CancelReason::find($cancelReasonId);

    if(!is_object($cancelReason)){
      Error::trigger('order.cancel', ["cancel_reason_id"=>"reason not found"]);
    }

    $orderStatus = \App\Model\OrderStatus::where("order_status_title", "like", "%\"Canceled\"%")->first();
    //echo '<pre>'.print_r($orderStatus, true).'</pre>'; exit;

    // $order->cancel_reason = $cancelReason->reason;
    $order->prev_order_status_id = $order->order_status_id;
    $order->order_status_id = ($order->order_status_id == 3 && $refund_to != '')?10:$orderStatus->order_status_id;
    $order->order_status = $orderStatus->order_status_title;
    $order->cancel_reason_id = $cancelReasonId;
    $order->status_cancelled = ($order->order_status_id == 3 && $refund_to != '')?Null:date('Y-m-d H:i:s');
    $order->refund_to = ($refund_to == 'refund_to_wallet')?'wallet':(($refund_to == 'refund_to_bank')?'bank':'');

    $doCancelOrder = false;
    //Ayesha 25-6-2021 call api to driver app if order is cancelled from 'Shipped/Assigned' state. and cancel order only if result is 200.
    if ($order->prev_order_status_id == 3 || $order->prev_order_status_id == 13) {
      $delivery = \App\Model\Delivery::with('delivery_trip.vehicle.driver')->where('order_id', $orderId)->first();

      if (is_object($delivery)) {
        $delivery = $delivery->toArray();

        if ($delivery['deleted_at'] == null) {
          if (isset($order->address) && isset($order->address->location)) {
            $storeId = $order->address->location->store_id;
            //$storeId = 1000;

            if (isset($delivery['delivery_trip'])) {
              $notification = new  \App\Notification\PushNotification();
              $response = $notification->sendCancelNotificationToDriver($orderId, $storeId, $delivery['delivery_trip']);
              if ($response == 1) {
                //cancel order

                $doCancelOrder = true;
              }
            }
          }
        } else {
          $doCancelOrder = true;
        }


        // if (isset($delivery['delivery_trip'])) {
        //   $driver_fcm_token = $delivery['delivery_trip']['vehicle']['driver']['fcm_token_for_driver_app'];

        //   if (isset($driver_fcm_token) && strlen($driver_fcm_token) > 0) {
        //     \Modules\Admin\Model\Template::sendOrderCancelNotificationToDriver($driver_fcm_token, $delivery['delivery_trip']['vehicle']['driver_id'], $order->order_number);
        //   }
        // }
      } else {
        $doCancelOrder = true;
      }
    }else {
      $doCancelOrder = true;
    }

    if ($doCancelOrder) {
      try {
        $orderSave = $order->save();
        $log['order_id'] = $order->order_id;
        $log['order_status_id'] = $order->order_status_id;
        $log['source_id'] = $device;
        $log['user_id'] = $userID;
        $orderLog =  new \App\Model\OrderLog();
        $orderLog->add($log);

        $status = \App\Model\Order::where('order_id',$orderId)->whereIn('prev_order_status_id',[2,5,13,14])->get();
        if(!$status->isEmpty()){
        $items = \App\Model\OrderItem::select('product_id','quantity')->where('order_id',$orderId)->get()->toArray();
        foreach($items as $item){
          $material = Product::where('product_id',$item['product_id'])->value('material');
          $reserved_stock = ProductStock::where('material',$material);
          //$item['quantity']
          if($reserved_stock->value('reserved_stock') > 0){
            $reserved_stock->decrement('reserved_stock',$item['quantity']);
          }
        }
      }

        // if($order->erp_id == Null){
          $customer = Customer::find($order->customer_id);
          if($customer->account_type_id == 2){
            $customer = Customer::find($customer->parent_id);
          }
          if($customer->account_type_id != 0){
            $customer->current_balance = $customer->current_balance+$order->grand_total;
            $customer->save();
          }
        // }

        if($order->order_status_id != 10){
          $online_amount = isset($order->payment->amount)?$order->payment->amount:0;
          $wallet_amount = isset($order->walletAmountErp->amount)?$order->walletAmountErp->amount:0;
          $f_amount = $online_amount+$wallet_amount;

          if($refund_to == '' && $wallet_amount > 0){
            $refund_to = 'refund_to_wallet';
          }
          $refund_to_wallet = \App\Model\Option::getValueByKey('REFUND_TO_WALLET');
          $refund_to_bank = \App\Model\Option::getValueByKey('REFUND_TO_BANK');
          $manual_refund = \App\Model\Option::getValueByKey('MANUAL_REFUND');

          if($refund_to == 'refund_to_wallet' && $refund_to_wallet == 1){
            if($f_amount > 0){
              $mod = new WalletTransaction();
              $wallet = [
                'type' => 1,
                'customer_id' => $order->customer_id,
                'order_id' => $order->order_id,
                'amount' => $f_amount,
                'available_amount' => $f_amount,
                'reference' => rand(0, strtotime(date('Y-m-d H:i:s'))),
                'reason_id' => 2,
                'mode' => 'credit',
                'added_by' => 1,
                'valid_till' => Null,
              ];
              $mod->add($wallet);
            }
          }elseif($refund_to == 'refund_to_bank' && $refund_to_bank == 1) {
            if($online_amount > 0){
              $refund_model = new HyperPay();
              $refund_model->refundPayment($order->order_id);
            }
            if($wallet_amount > 0 && $refund_to_wallet == 1){
              $mod = new WalletTransaction();
              $wallet = [
                'type' => 1,
                'customer_id' => $order->customer_id,
                'order_id' => $order->order_id,
                'amount' => $wallet_amount,
                'available_amount' => $wallet_amount,
                'reference' => rand(0, strtotime(date('Y-m-d H:i:s'))),
                'reason_id' => 2,
                'mode' => 'credit',
                'added_by' => 1,
                'valid_till' => Null,
              ];
              $mod->add($wallet);
            }
          }

          if ($order->wallet_discount != Null && $order->wallet_discount > 0 && $refund_to_wallet == 1) {
            $mod = new WalletTransaction();
            $wallet = [
              'type' => 3,
              'customer_id' => $order->customer_id,
              'order_id' => $order->order_id,
              'amount' => $order->wallet_discount,
              'available_amount' => $order->wallet_discount,
              'reference' => rand(0, strtotime(date('Y-m-d H:i:s'))),
              'reason_id' => 2,
              'mode' => 'credit',
              'added_by' => 1,
              'valid_till' => Null,
            ];
            $mod->add($wallet);
          }
        }

        //Ayesha: 26/11/2020 send notification to customer if customer cancels order.
        $result = \App\Model\NotificationTemplate::sendOrderStatusNotifications($order->order_id);
        return $orderSave;

      }
      catch(\Exception $ex){
        echo $ex->getMessage(); exit;
      }
    }

    //return $orderSave;
  }

  function setGroupPromotion($data){

    $count = 0;
    if(isset($data['promotion_group'])){
      foreach ($data['promotion_group'] as $key => $value) {
        //Ayesha 4-6-2021 promotion_group may contain variant array.
        if (is_array($value)) {
          foreach($value as $variant_qty) {
            $count = $count + $variant_qty;
          }
        } else {
          $count = $count+$value;
        }
      }

      foreach ($data['promotion_group'] as $key => $value) {
        $model = new CustomerOrderItem();
        if ($count <= $data['group_prom_check']) {
          if($value != Null){

            if (is_array($value)) {
              foreach($value as $variant_id => $variant_qty) {
                $model = new CustomerOrderItem();
                $item = CustomerOrderItem::where("order_id", "=", $data['order_id'])->where('product_id', $key)->where('variant_id', $variant_id)->get()->first();
                if (is_object($item)) {
                  $item->foc_items += $variant_qty;
                  $item->save();
                } else {
                  $model->order_id = $data['order_id'];
                  $model->product_id = $key;
                  $model->variant_id = $variant_id;
                  $model->quantity = 0;
                  $model->price = 0;
                  $model->unit_price = 0;
                  $model->foc_items = $variant_qty;
                  $model->status = 1;
                  $model->save();
                }
              }
            } else {
              $item = CustomerOrderItem::where("order_id", "=", $data['order_id'])->where('product_id', $key)->get()->first();
              if(is_object($item)){
                $item->foc_items += $value;
                $item->save();
              }
              else{
                $model->order_id = $data['order_id'];
                $model->product_id = $key;
                $model->quantity = 0;
                $model->price = 0;
                $model->unit_price = 0;
                $model->foc_items = $value;
                $model->status = 1;
                $model->save();
              }
            }
          }
        }
      }
    }
  }

  // public function sendMail($data)
  // {
  //   $objDemo = new \stdClass();
  //   $objDemo->order_number = $data['order_number'];
  //   $objDemo->url = $data['url'];
  //   $objDemo->sender = 'Ojen Water';

  //   Mail::to($data['email'])->send(new DemoEmail($objDemo));
  //   return true;
  // }

}
