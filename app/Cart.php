<?php

namespace App;
use App\Model\Cart as CustomerCart;
use App\Model\CartItem;
use App\Model\Product;
use App\Model\Order;
use App\Model\Address;
use App\Model\Location;
use App\Message\Error;
use App\Model\ExtCompContract;
use App\Model\ProductStock;


class Cart{

  protected $products = [];
  protected $paymentMethod;
  protected $shippingMethod;
  protected $shippingAddress;
  protected $paymentAddress;
  protected $cart;
  protected $cartId;
  protected $cartItem;


  function __construct(){

    // $this->cartId = session('cart_id');
    // $customer = session('customer');
    if(!$this->cartId){
      try{
        $this->cart = CustomerCart::create([
          "total" => 0.00,
          "status" => 1,
          "customer_id" => (int) isset($customer['customer_id']) ? $customer['customer_id']:0
          // "customer_id" => (int) $customer['customer_id']
        ]);
      }
      catch(\Exception $ex){
        echo $ex->getMessage(); exit;
      }
      $this->cartId = $this->cart->cart_id;
      // session(['cart_id'=>$this->cartId]);
    }
    else {
      $this->cart = CustomerCart::onWriteConnection()->find($this->cartId);
      if(!\is_object($this->cart)){
        // session()->forget('cart_id');
        $this->__construct();
      }
      if(!$this->cart->customer_id){
        if(is_array($customer)){
          $this->cart->customer_id = $customer['customer_id'];
          $this->cart->save();
          $this->cart = CustomerCart::onWriteConnection()->find($this->cartId);
        }
      }
    }
  }

  function addProduct($product_id, $quantity = 0, $foc_item=0, $price=0, $address_id){

   
    // $product = \App\Model\Product::find($product_id);

    $cartItem = CartItem::where([
      ["cart_id", '=', $this->cartId],
      ["product_id", '=', $product_id]
      ])->first();
      // print_r($cartItem); exit;
      if( is_object($cartItem)){
        $product = Product::select('product_id','product_name','material')->find($product_id);
        $location = Address::find($address_id);
        $store_id = Location::where('location_id',$location->location_id)->value('store_id');
        $stock_check = ProductStock::select('stock_id','stock','reserved_stock')->where(['material'=>$product->material,'store_id'=>$store_id])->first();
        if($stock_check && $quantity > ($stock_check->stock-$stock_check->reserved_stock)){
          $diff = $quantity - ($stock_check->stock-$stock_check->reserved_stock);
          $prod_name = json_decode($product->product_name,true);
          if(\App\Language::getCurrentLanguage() == 'en'){
            $message = "We only have {($stock_check->stock-$stock_check->reserved_stock)} {$prod_name['en']} left please remove {$diff} to proceed";
          }else {
            $message = "لم يتبق لدينا سوى {($stock_check->stock-$stock_check->reserved_stock)} {$prod_name['ar']} يرجى إزالة {$diff} للمتابعة";
          }
          Error::trigger("cartitem.add", [$message]);
          return [];
        }
        $cartItem->quantity += $quantity;
        $cartItem->foc_items = $foc_item;
        $cartItem->unit_price = $price;
        $cartItem->price = $price * $cartItem->quantity;
        // $cartItem->variant_id = $variant_id;
        // $cartItem->price = $product->price * $cartItem->quantity;
        $cartItem->save();
        //$cartItem =  $cartItem;
      }
      else {

        $product = Product::select('product_id','product_name','material')->find($product_id);
        // $store_id = session('store_id_web');
        $location = Address::find($address_id); //To fetch store_id usin address_id
        $store_id = Location::where('location_id',$location->location_id)->value('store_id');

        $stock_check = ProductStock::select('stock_id','stock','reserved_stock')->where(['material'=>$product->material,'store_id'=>$store_id])->first();
      
        if($stock_check && $quantity > ($stock_check->stock-$stock_check->reserved_stock)){
          $diff = $quantity - ($stock_check->stock-$stock_check->reserved_stock);
          $prod_name = json_decode($product->product_name,true);
          $st = ($stock_check->stock-$stock_check->reserved_stock);
          if(\App\Language::getCurrentLanguage() == 'en'){
            $message = "We only have {$st} {$prod_name['en']} left please remove {$diff} to proceed";
          }else {
            $message = "لم يتبق لدينا سوى {$st} {$prod_name['ar']} يرجى إزالة {$diff} للمتابعة";
          }
          Error::trigger("cartitem.add", [$message]);
          return [];
        }

        $cartItem = new CartItem();
        $cartItem = $cartItem->add([
          "cart_id" => $this->cartId,
          "product_id" => $product_id,
          "unit_price" => $price,
          "price" => $price * $quantity,
          "quantity" => $quantity,
          "foc_items" => $foc_item,
          // "variant_id" => $variant_id,
          "status" => 1
        ]);
      }

      $this->calculateTotal();
      return $this->cart;
   
    }

    function reLoad(){
      $cartIds = [];
      $cartItemIds = [];
      // $this->cartId = session('cart_id');
      // $customer = session('customer');
      // $type = session('address_type');
      if(!$this->cartId){
        $cart = CustomerCart::where("customer_id", $customer['customer_id'])->orderBy('cart_id', 'desc')->first();
        if(!is_object($cart)){
          /*$cart = CustomerCart::create([
          "total" => 0.00,
          "status" => 1,
          "customer_id" => (int) $customer['customer_id']
        ]);
      }
      else{
      */
      $this->cart = $cart;
      $this->cartId = $cart->cart_id;
      // session(['cart_id'=>$cart->cart_id]);
    }
  }

  $carts = CustomerCart::where([
    ["customer_id", "=", $customer['customer_id']],
    ["cart_id", "!=", $this->cartId],
    ])->orderBy('cart_id', 'desc')->get()->toArray();

    //  echo '<pre>'.print_r($carts, true).'</pre>'; exit;

    for($i=0, $count = count($carts); $i < $count; $i++){

      if($carts[$i]['cart_id'] != $this->cartId){
        $cartIds[] = $carts[$i]['cart_id'];
      }

      $cartItems = \App\Model\CartItem::where("cart_id", $carts[$i]['cart_id'])->get()->toArray();
      for($j=0, $counter = count($cartItems); $j < $counter; $j++){
        unset($cartItems[$j]['cart_item_id']);
        $cartItems[$j]['cart_d'] = $this->cartId;
        //$cartItem = \App\Model\CartItem::create($cartItems[$j]);
        // if ($cartItems[$j]['variant_id'] == null) {
          $price = getProduct($customer["customer_id"],$cartItems[$j]['product_id'],$type);
        // }else {
          // $price = getVariant($customer["customer_id"],$cartItems[$j]['product_id'],$type,$cartItems[$j]['variant_id']);
        // }
        $price = $price->price;
        $this->addProduct($cartItems[$j]['product_id'], $cartItems[$j]['quantity'],0,$price);

        //$cartItemIds[] = $cartItem->cart_item_id;
      }
    }

    if(count($cartIds) > 0){
      CustomerCart::whereIn("cart_id", $cartIds)->delete();
      \App\Model\CartItem::whereIn("cart_id", $cartIds)->delete();
    }

  }

  function removeItem($productId){
    //$cartItem = new CartItem();
    $isDeleted = CartItem::where([
      ["cart_id", "=", $this->cartId],
      ["product_id", "=", $productId]
      ])->delete();

      $this->calculateTotal();
      return $isDeleted;
    }

    function updatePrice(){
      $products = CartItem::where('cart_id',$this->cartId)->get()->toArray();
      foreach ($products as $key => $value) {
        $this->setQuantity($value['product_id'],$value['quantity'],0);
      }
    }

    function setQuantity($product_id, $quantity = 1, $foc_item = 0){

      // $type = session('address_type');
      $customer = \App\Model\Customer::getLoggedInCustomer();


     if ($customer == null) {
        $customer_id = 0;
     }else{
         $customer_id = $customer['customer_id'];
     }

      // if ($variant_id == null) {
        $product = getProduct($customer_id,$product_id,$type);
      // }else {
        // $product = getVariant($customer_id,$product_id,$type,$variant_id);
      // }

      if(is_object($product)){

        $product_check = Product::select('product_id','product_name','material')->find($product_id);
        // $store_id = session('store_id_web');
        $stock_check = ProductStock::select('stock_id','stock','reserved_stock')->where(['material'=>$product_check->material,'store_id'=>$store_id])->first();
        if($stock_check && $quantity > ($stock_check->stock-$stock_check->reserved_stock)){
          $diff = $quantity - ($stock_check->stock-$stock_check->reserved_stock);
          $st = ($stock_check->stock-$stock_check->reserved_stock);
          $prod_name = json_decode($product_check->product_name,true);
          if(\App\Language::getCurrentLanguage() == 'en'){
            $message = "We only have {$st} {$prod_name['en']} left please remove {$diff} to proceed";
          }else {
            $message = "لم يتبق لدينا سوى {$st} {$prod_name['ar']} يرجى إزالة {$diff} للمتابعة";
          }
          Error::trigger("cartitem.update", [$message]);
          return [];
        }

        CartItem::where([
          ["product_id", "=", $product_id],
          ["cart_id", "=", $this->cartId],
          ])->update([
            "quantity" => $quantity,
            "foc_items" => $foc_item,
            "unit_price" => $product->price,
            "price" => $quantity * $product->price,
            // "variant_id" => $variant_id
          ]);
        }
        else{
          CartItem::where([
            ["product_id", "=", $product_id],
            ["cart_id", "=", $this->cartId],
            ])->delete();
          }
          $this->calculateTotal();
        }

        function setPaymentMethod(\App\Payment $payment){
          $this->paymentMethod = $payment;
        }

        function getPaymentMethod(){
          return $this->paymentMethod;
        }

        function setShippingMethod(\App\Shipping $shipping){
          $this->shippingMethod = $shipping;
        }

        function getShippingMethod(){
          return $this->shippingMethod;
        }

        function setPaymentAddress(\App\Model\Address $address){
          $this->paymentAddress = $address;
        }

        function getPaymentAddress(){
          return $this->paymentAddress;
        }

        function setShippingAddress(\App\Model\Address $address){
          $this->shippingAddress = $address;
        }

        function getShippingAddress(){
          return $this->shippingAddress;
        }

        function calculateTotal(){

          $total = 0.00;

          $vat = 0.00;

          $items = CartItem::onWriteConnection()->where("cart_id", $this->cartId)->get()->toArray();
          // print_r($items);exit;

          for($i=0, $count = count($items); $i < $count; $i++){
            $total += (float) $items[$i]['price'];
          }

          $this->cart->total = (float) $total;

          // $cart = CustomerCart::onWriteConnection()->find($this->cartId);

          // $this->cart->total -= $cart->discount;

          $this->cart->save();
            $this->cart = CustomerCart::onWriteConnection()->find($this->cartId);
          // $this->cart = CustomerCart::onWriteConnection()->find($this->cartId);

        }


        function applyPromocode($code,$total=0,$channel_id=1,$stc='',$customer_id=0){

          $accessAllowed = false;
          $status = 1;
          if($stc == 'stc'){
            $status = 9;
          }

          $promo = \App\Model\Promocode::where("promo_code_access", $code)
          ->where('start_date', '<=', date('Y-m-d H:i:s'))
          ->where('end_date', '>=', date('Y-m-d H:i:s'))
          // ->whereJsonContains('channels', $channel_id)
          ->where(function ($query) use ($channel_id) {
            $query->whereJsonContains('channels', $channel_id)
            ->orWhereJsonLength('channels', 0);
          })->whereRaw("(max_use > code_used or max_use = 0)")
          ->where('status',$status)
          ->first();

          if(isset($promo->usage_per_customer) && $promo->usage_per_customer > 0){
            $id = $promo->promocode_id;
            $promo_count = Order::where('promocode_id',$id)->where('order_status_id','<>',6)
            ->where('customer_id',$customer_id)->get()->count();

            if($promo_count >= $promo->usage_per_customer){
              Error::trigger('promocode.apply', "This promocode is not available");
              return false;
            }
          }
          if(isset($promo->for_company) && $promo->for_company == 1 && $customer_id > 0){
            $default_prom = ExtCompContract::with(array(
              'company.promocode' => function ($query)
              {
                $query->where('promocodes.start_date', '<=', date('Y-m-d H:i:s'));
                $query->where('promocodes.end_date', '>=', date('Y-m-d H:i:s'));
                $query->where('promocodes.status', 1);
                }
              ))
              ->where('customer_id', $customer_id)
              ->where('status',1)
              ->get()
              ->first();

              if (isset($default_prom->company->promocode) && $default_prom->company->promocode != Null)
              {

              }else{
                Error::trigger('promocode.apply', "This promocode is not available");
                return false;
              }
          }

          if(is_object($promo)){
            if($promo->available_for == 'callcenter') {
              Error::trigger('promocode.apply', "This promocode is not available");
              $accessAllowed = false;
            }
            else{
              $cartInfo = $this->toArray();
              $quantity = 0;
              for($i=0, $count = count($cartInfo['items']); $i < $count; $i++){

                // if($quantity < $cartInfo['items'][$i]['quantity']){
                $quantity += $cartInfo['items'][$i]['quantity'];
                // }
              }
              // dd($quantity);
              if( $quantity >= $promo->min_quantity ){

                $quantity = 0;

                $cartQuantity = $this->getQuantityCount();

                if( $cartQuantity >= $promo->min_quantity ){
                  //echo count($cartInfo['items']); exit;
                  if($cartInfo['total'] >= $promo->min_price && $promo->type == 'after_vat'){
                    $accessAllowed = true;
                    $cart = CustomerCart::onWriteConnection()->find($this->cartId);
                    $vat = \App\Model\Option::getValueByKey('VAT_IN_PERCENT');
                    $vat = round($vat * $cartInfo['total'] / 100, 2);
                    $total = $vat+$cartInfo['total'];
                    $cart->discount = $promo->discount;
                    if($promo->discount_type =='percentage'){
                      $cart->discount = $total*(($promo->discount)/100);
                    }
                    $cart->promocode_id = $promo->promocode_id;
                    $cart->promocode_type = $promo->type;
                    $cart->save();

                    $this->cart = CustomerCart::onWriteConnection()->find($this->cartId);
                  }
                  elseif($cartInfo['total'] >= $promo->min_price){
                    $accessAllowed = true;
                    $cart = CustomerCart::onWriteConnection()->find($this->cartId);
                    $cart->discount = $promo->discount;
                    if($promo->discount_type =='percentage'){
                      $cart->discount = $cartInfo['total']*(($promo->discount)/100);
                    }
                    $cart->promocode_id = $promo->promocode_id;
                    $cart->promocode_type = $promo->type;
                    $cart->save();

                    $this->cart = CustomerCart::onWriteConnection()->find($this->cartId);
                    
                  }
                  else {
                    $accessAllowed = false;
                    Error::trigger('promocode.apply', "Min order value should be {$promo->min_price}!");
                  }
                }
                else {
                  Error::trigger('promocode.apply', "Min quantity should be {$promo->min_quantity}!");
                  $accessAllowed = false;
                }
              }
              else {
                Error::trigger('promocode.apply', "Min quantity should be {$promo->min_quantity}!");
                $accessAllowed = false;
              }
              if($accessAllowed){
                // $address_id = session("address_id");
                if(isset($address_id)){
                  $city = \App\Model\Address::with('location:location_id,parent_id')->select('address_id','location_id')->find($address_id);
                  $available_city = json_decode($promo->locations,true);
                  if(!empty($available_city)){
                    if(!in_array($city->location->parent_id, $available_city)){
                      Error::trigger('promocode.apply', "This promocode is not available");
                      $accessAllowed = false;
                    }
                  }
                }
              }
              if($accessAllowed){
                $promo_prods = json_decode($promo->products,true);
                if(!empty($promo_prods)){
                  $accessAllowed = false;
                  foreach ($cartInfo['items'] as $key => $value) {
                    Error::trigger('promocode.apply', "This promocode is not available");
                    if(in_array($value['product_id'], $promo_prods)){
                      $accessAllowed = true;
                    }
                  }
                }
              }
              return $accessAllowed;
            }
          }
          Error::trigger('promocode.apply', "This promocode is not available");
          return false;

        }

        function toArray(){
          if(!is_object($this->cart)){
            return ["items" => [], "item_total" => 0];
          }
          $cart = $this->cart->getAttributes();
          // $customer = session('customer');
            $channel_id = 1;
            if(isset($customer['channel_id'])){
              $channel_id = $customer['channel_id'];
            }
          //print_r($this->cart); exit;
          $cart['items'] = CartItem::onWriteConnection()->with(["product.images",'Channelproduct' => function ($query) use ($channel_id) {
              $query->where(['channel_id' => $channel_id]);
            }])->where("cart_id", $this->cartId)->get()->toArray();
          $cart['item_total'] = CartItem::onWriteConnection()->where("cart_id", $this->cartId)->sum('price');
          return $cart;
        }

        function removeDiscount(){
          $cart = CustomerCart::onWriteConnection()->find($this->cartId);
          $cart->discount = 0.00;
          $cart->promocode_id = 0;
          return $cart->save();

        }

        function getQuantityCount(){
          return CartItem::where("cart_id", $this->cart->cart_id)->sum('quantity');
        }

        function reorder($order_id){
          $order = \App\Model\Order::find($order_id);
          if(is_object($order)){
            $order_data = $order->toArray();
            $this->cart = CustomerCart::create([
              "customer_id" => $order["customer_id"],
              "status" => 1,
              "discount" => 0
            ]);
            $this->cartId = $this->cart->cart_id;
          }

          $address = Address::find($order->shipping_address_id);

          // session([
          //   'address_id' => $order->shipping_address_id,
          //   'cart_id' => $this->cartId,
          //   'address_type' => $address->type
          // ]);

          $items = \App\Model\OrderItem::where("order_id", $order_id)->get();
          //echo '<pre>'.print_r($items, true).'</pre>'; exit;
          foreach($items as $item){

            if(is_object($item)){
              // if($item->variant_id == null){
                $price = getProduct($order["customer_id"],$item->product_id,$address->type);
              // }else {
                // $price = getVariant($order["customer_id"],$item->product_id,$address->type,$item->variant_id);
              // }
              $price = $price->price;
              //echo '<pre>'.print_r($item, true).'</pre>'; exit;
              $this->addProduct($item->product_id, $item->quantity,0,$price);
            }
          }

          //echo '<pre>'.print_r($this->toArray(), true).'</pre>'; exit;

        }

        function empty(){
          $isDeleted = CartItem::where("cart_id", $this->cart->cart_id)->delete();
          $this->removeDiscount();
          $this->calculateTotal();
          return $isDeleted;
        }

        // function setDeliveryTime($deliveryTime){
        //   $this->cart->delivery_time = $deliveryTime;
        //   try{
        //     $this->cart->save();
        //     return true;
        //   }
        //   catch(\Exception $ex){
        //     Error::trigger('set_delivery_time', $ex->getMessage());
        //     return false;
        //   }
        // }

      }
