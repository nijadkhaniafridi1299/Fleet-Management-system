<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Cart as Validator;

class Cart extends Model
{
  use Validator;

  protected $primaryKey = "cart_id";
  protected $table = "carts";
  protected $fillable = ['customer_id', 'payment_method', 'status', 'discount', 'total', 'delivery_time','promocode_type'];
  protected $attributes = ['status'=> 1, "discount" => 0.00, 'total' => 0.00, 'promocode_type' => 'before_vat'];
  public $timestamps = true;

  function customer(){
    return $this->belongsTo('App\Model\Customer', 'customer_id');
  }

  function items(){
    return $this->hasMany('App\Model\CartItem', 'cart_id');
  }

  function add($data){

    try{
      return parent::add($data);
    }
    catch(\Exception $ex){
      Error::trigger("cart.add", [$ex->getMessage()]) ;
    }
  }

  function change(array $data, $cart_id){

    $cart = static::find($cart_id);

    try{
      $cart->save();
    }
    catch(Exception $ex){
      Error::trigger("cart.change", [$ex->getMessage()]) ;
    }

  }

}
