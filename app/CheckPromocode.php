<?php

namespace App;
use App\Model\Product;
use App\Model\Location;
use App\Message\Error;
use App\Model\Promocode;
use App\Model\Customer;
use App\Model\Order;
use App\Model\ExtCompContract;

class CheckPromocode{
  protected $city_id="";
  protected $promocode="";
  protected $order_qty= 0;
  protected $order_value= 0;
  protected $invalid_city =0;
  protected $valid_cities = array();
  protected $valid_products = array();
  protected $promodata = [ "promo_expired_error" => "",
  "promo_city_error" => "",
  "promo_min_value_error" => "",
  "promo_min_qty_error" => "",
  "promo_max_used_error" => "",
  "promo_not_found" => "",
  "is_promo_valid" => "",
  "minimum_order_value" => "",
  "minimum_order_qty" => "",
  "discount_level" => "",
  "message_en" => "",
  "message_ar" => "",
  "after_vat" => "0",
];

function __construct(){
}

function isValid($data,$stc=''){
  // $promocode = Promocode::where("promo_code_access", $data['promo_code'])->where("status","=",1)->first();
  $customer = Customer::find($data['client_id']);
  $channel_id = $customer->sub_channel_id;
  if($customer->account_type_id == 2){
    $parent = Customer::find($customer->parent_id);
    $channel_id = $parent->sub_channel_id;
  }
  $status = 1;
  if($stc == 'stc'){
    $status = 9;
  }
  $promocode = Promocode::where("promo_code_access", $data['promo_code'])
  ->where("status",$status);
  

  if(isset($data['add_type']) && $data['add_type'] == 2){
    $channel = \App\Model\Channel::where('channel_code', 'Mosque')->get()->first();
    $promocode->where(function ($query) use ($channel) {
      $query->whereJsonContains("channels",$channel->channel_id)
      ->orWhereJsonLength('channels', 0);
    });
  }
  else{
    $promocode->where(function ($query) use ($channel_id) {
      $query->whereJsonContains("channels",$channel_id)
      ->orWhereJsonLength('channels', 0);
    });
    // $promocode->whereJsonContains("channels","=",$channel_id);
  }
  $promocode = $promocode->first();

  if(isset($promocode->for_company) && $promocode->for_company == 1){
    $default_prom = ExtCompContract::with(array(
      'company.promocode' => function ($query)
      {
        $query->where('promocodes.start_date', '<=', date('Y-m-d H:i:s'));
        $query->where('promocodes.end_date', '>=', date('Y-m-d H:i:s'));
        $query->where('promocodes.status', 1);
        }
      ))
      ->where('customer_id', $data['client_id'])
      ->where('status',1)
      ->get()
      ->first();

      if (isset($default_prom->company->promocode) && $default_prom->company->promocode != Null)
      {

      }else{
        $this->promodata['promo_not_found'] = 'promo_not_found';
        $this->promodata['is_promo_valid'] = "0";
        $promocode = [];
      }
  }
  // dd($promocode);
  if(!empty($promocode))
  {
    $promocode = $promocode->toArray();
    if ( ( date('Y-m-d H:i:s') >= $promocode["start_date"]) && ( date('Y-m-d H:i:s') <= $promocode["end_date"] ) ) // Date Filter
    {
      $this->promodata['value'] = $promocode['discount']; // Setting discount
      $this->promodata['type'] = $promocode['discount_type'];
      $promocode['products'] = json_decode($promocode['products']);
      $promocode['locations'] = json_decode($promocode['locations']);
      $this->promodata['minimum_order_value'] = $promocode['min_price'];
      $this->promodata['minimum_order_qty'] = $promocode['min_quantity'];
      $this->promodata['discount_level'] = "order";

      if ($promocode['available_for'] == 'callcenter') // Available For Filter
      {
        $this->promodata['promo_not_found'] = "not found";
        $this->promodata['is_promo_valid'] = "0";
      }

      if(is_array($promocode['products']) && count($promocode['products']) > 0) // Product filter
      {
        for($i=0; $i < count($promocode['products']) ; $i++){

          if(!in_array($promocode['products'][$i], $this->valid_products) ){
            array_push($this->valid_products,$promocode['products'][$i]);
          }
        }

      }
      if(is_array($promocode['locations']) && count($promocode['locations']) > 0) // Product filter
      {
        for($i=0; $i < count($promocode['locations']) ; $i++){
          if($promocode['locations'][$i] == $data["city_id"]){ // Check user city
            $this->valid_cities = array();
            $this->invalid_city = "1";
            break;
          }
          if(!in_array($promocode['locations'][$i], $this->valid_cities) ){
            array_push($this->valid_cities,$promocode['locations'][$i]);
          }
        }

        if($this->invalid_city != 1){
          $this->promodata['promo_city_error'] = 'invalid_city';
          $this->promodata['is_promo_valid'] = "0";
        }

      }
      $this->promodata['valid_cities'] = $this->valid_cities;



      if(isset($data['cart']))
      {
        for($i=0; $i < count($data['cart']) ; $i++){
          $prodPrice = Product::where("product_id", $data['cart'][$i]['dish_id'])->where("status","=",1)->first()->toArray();
          $this->order_value += ($prodPrice['price'] * $data['cart'][$i]['count']) ;
          $this->order_qty += $data['cart'][$i]['count'] ;
        }
          $vat = \App\Model\Option::getValueByKey('VAT_IN_PERCENT');
      }

      if ($promocode['min_price'] != 0) // Min Price Filter
      {
        if( $this->order_value < $promocode['min_price']) {
          $this->promodata['promo_min_value_error'] = "order_value_less_than_promo_min_set_value";
          $this->promodata['minimum_order_value'] = $this->order_value ;
          $this->promodata['is_promo_valid'] = "0";
        }

      }

      if ($promocode['min_quantity'] != 0) // Min Qty Filter
      {
        if($this->order_qty < $promocode['min_quantity']){
          $this->promodata['promo_min_qty_error'] = "order_qty_less_than_promo_min_set_value";
          $this->promodata['promo_min_value_error'] = "order_value_less_than_promo_min_set_value";
          $this->promodata['promo_min_qty_val'] = $this->order_qty;
          $this->promodata['is_promo_valid'] = "0";

        }
      }

      if(!empty($promocode['code_used']) &&  $promocode['max_use'] != NULL && $promocode['max_use'] != 0) // Max usage filter
      {
        if ($promocode['max_use'] <= $promocode['code_used'] )
        {
          $this->promodata['promo_max_used_error'] = "promo_usage_limit_exeeded";
          $this->promodata['is_promo_valid'] = "0";

        }

      }
      $this->promodata['allowed_products'] = $this->valid_products;
      
      $id = $promocode['promocode_id'];
      $usage_per_customer = $promocode['usage_per_customer'];
      if(isset($usage_per_customer) && $usage_per_customer > 0){
        $promo_count = Order::where('promocode_id',$id)->where('order_status_id','<>',6)
        ->where('customer_id',$data['client_id'])->get()->count();

        
        if($promo_count >= $usage_per_customer){
          $this->promodata['promo_expired_error'] = "promo_expired";
          $this->promodata['message_en'] = 'Promocode maximum usage limit exceeded';
          $this->promodata['message_ar'] = '';
          $this->promodata['is_promo_valid'] = "0";
          
        }
      }

    }
    else
    {
      $this->promodata['promo_expired_error'] = "promo_expired";
      $this->promodata['is_promo_valid'] = "0";
    }

    if($promocode['type'] == 'after_vat'){
      $this->promodata['after_vat'] = "1";
      if(isset($this->order_value) && isset($vat)){
        $vat = $this->order_value * ($vat/100);
        if($this->promodata['value'] > ($this->order_value+$vat) && $promocode['discount_type'] == 'fixed')
        $this->promodata['value'] = (string) ($this->order_value+$vat);
      }
    }
  }
  else
  {
    $this->promodata['promo_not_found'] = 'promo_not_found';
    $this->promodata['is_promo_valid'] = "0";
  }


  if (empty($this->promodata['promo_expired_error'])
  && empty($this->promodata['promo_city_error'])
  && empty($this->promodata['promo_min_value_error'])
  && empty($this->promodata['promo_max_used_error'])
  && empty($this->promodata['promo_not_found'])
  && empty($this->promodata['promo_min_qty_error']) )
  {
    $this->promodata['is_promo_valid'] = "1";
  }

  unset($this->promodata['promo_expired_error']);
  unset($this->promodata['promo_city_error']);
  unset($this->promodata['promo_min_value_error']);
  unset($this->promodata['promo_min_qty_error']);
  unset($this->promodata['promo_max_used_error']);
  unset($this->promodata['promo_not_found']);
  // unset($this->promodata['allowed_products']);
  unset($this->promodata['minimum_order_value']);
  unset($this->promodata['minimum_order_qty']);
  unset($this->promodata['valid_cities']);

  if($this->promodata['is_promo_valid'] == 0){
    $this->promodata['message_en'] = 'Invalid Promocode';
    $this->promodata['message_ar'] = 'الكود المستخدم غير صحيح';
  }
  
  return  $this->promodata;



  //    return $data;

}





}
