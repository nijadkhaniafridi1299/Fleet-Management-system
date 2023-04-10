<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Promocode as Validator;

class Promocode extends Model
{
  use Validator;

  protected $primaryKey = "promocode_id";
  protected $table = "promocodes";
  protected $fillable = ['channels','start_date', 'end_date', 'min_quantity', 'min_price', 'promo_code_access', 'max_use',
  'discount','discount_type', 'products', 'locations', 'status', 'code_used','available_for', 'brand_ambassador','before_discount','refund_wallet'];
  protected $attributes = ['code_used' => Null, 'status' => 1, 'refund_wallet' => 0];
  public $timestamps = true;

  function channel(){
    return $this->belongsTo('App\Model\Channel', 'channel_id');
  }

  function add($data){
    //   echo '<pre>'.print_r($data, true).print_r($_POST, true).'</pre>'; exit;

    if($data['radio_handler'] == 'amount'){
      $data['discount_type'] = 'fixed';
    }
    else{
      $data['discount_type'] = 'percentage';
    }
    unset($data['radio_handler']);
    $data['start_date'] = (string) $data['start_date'];
    $data['end_date'] = (string) $data['end_date'];
    $data['min_quantity'] = (int) $data['min_quantity'];
    $data['min_price'] = (int) $data['min_price'];
    $data['promo_code_access'] = (string)$data['promo_code_access'];
    $data['max_use'] = (int) $data['max_use'];
    $data['discount'] = (int) $data['discount'];

    //$data['locations'] = (string) $data['locations'];
    //   $data['status'] = 1;
    $data['available_for'] = (string) $data['available_for'];
    $data['brand_ambassador'] = (string) $data['brand_ambassador'];
    if(isset($data['allProducts'])){
      $data['products'] = json_encode([], JSON_UNESCAPED_UNICODE);
    }
    else{
      $data['products'] = json_encode($data['products'], JSON_UNESCAPED_UNICODE);
    }

    if(isset($data['allCities'])){
      $data['locations'] = json_encode([], JSON_UNESCAPED_UNICODE);
    }
    else{
      $data['locations'] = json_encode($data['cities'], JSON_UNESCAPED_UNICODE);
    }
    foreach ($data['channels'] as $key => $value) {
      $data['channels'][$key] = (int) $value;
    }

    $data['channels'] = json_encode($data['channels'], JSON_UNESCAPED_UNICODE);

    unset($data['allProducts']);
    unset($data['allCities']);
    unset($data['cities']);
    // dd($data);
    //   $data['products'] = json_encode($data['products'], JSON_UNESCAPED_UNICODE);
    //   $data['locations'] = json_encode($data['locations'], JSON_UNESCAPED_UNICODE);


    try{
      $promocode = parent::add($data);
      if(is_array($promocode)){
        return [];
      }
      else{
        return $promocode->toArray();
      }
    }
    catch(\Exception $ex){
      Error::trigger("promocode.add", [$ex->getMessage()]);
    }
  }

  function change(array $data, $promocode_id){

    $promocode = static::find($promocode_id);

    if($data['radio_handler'] == 'amount'){
      $data['discount_type'] = 'fixed';
    }
    else{
      $data['discount_type'] = 'percentage';
    }
    unset($data['radio_handler']);

    if(isset($data['allProducts'])){
      $promocode->products = json_encode([], JSON_UNESCAPED_UNICODE);
    }
    else{
      $promocode->products = json_encode($data['products'], JSON_UNESCAPED_UNICODE);
    }

    if(isset($data['allCities'])){
      $promocode->locations = json_encode([], JSON_UNESCAPED_UNICODE);
    }
    else{
      $promocode->locations = json_encode($data['cities'], JSON_UNESCAPED_UNICODE);
    }

    foreach ($data['channels'] as $key => $value) {
      $data['channels'][$key] = (int) $value;
    }

    $promocode->channels = json_encode($data['channels'], JSON_UNESCAPED_UNICODE);

    unset($data['allProducts']);
    unset($data['allCities']);

    // $promocode->products = json_encode($data['products'], JSON_UNESCAPED_UNICODE);
    // $promocode->locations = json_encode($data['locations'], JSON_UNESCAPED_UNICODE);


    $promocode->start_date = (string) $data['start_date'];
    $promocode->end_date = (string) $data['end_date'];
    $promocode->min_quantity = (int) $data['min_quantity'];
    $promocode->min_price = (int) $data['min_price'];
    $promocode->promo_code_access = (string) $data['promo_code_access'];
    $promocode->max_use = (int) $data['max_use'];
    $promocode->discount = (int) $data['discount'];

    //$promocode->locations = (string) $data['locations'];
    $data['brand_ambassador'] = (string) $data['brand_ambassador'];
    $data['available_for'] = (string) $data['available_for'];


    // $data['first_name'] = json_encode($data['first_name'], JSON_UNESCAPED_UNICODE);
    // $data['last_name'] = json_encode($data['last_name'], JSON_UNESCAPED_UNICODE);

    try{
      $promocode->save();
      if(is_array($promocode)){
        return [];
      }
      else{
        return $promocode->toArray();
      }
    }
    catch(\Exception $ex){
      Error::trigger("promocode.change", [$ex->getMessage()]) ;
    }

  }

}
