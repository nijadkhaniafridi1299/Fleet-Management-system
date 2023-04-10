<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CartItem as Validator;

class CartItem extends Model
{
  use Validator;

  protected $primaryKey = "cart_item_id";
  protected $table = "cart_items";
  protected $fillable = ['cart_id', 'product_id', 'quantity', 'unit_price', 'status', 'price', 'foc_items','variant_id'];
  protected $attributes = ['status'=> 1];
  public $timestamps = true;

  function cart(){
    return $this->belongsTo('App\Model\Cart', 'cart_id');
  }

  function product(){
    return $this->belongsTo('App\Model\Product', 'product_id');
  }

  // function variant(){
  //   return $this->belongsTo('App\Model\Variant', 'variant_id', 'variant_id');
  // }

  function Channelproduct(){
    return $this->belongsTo('App\Model\ChannelProductPricing', 'product_id', 'product_id');
  }

  function add($data){
    
    try{
      return parent::add($data);
    }
    catch(\Exception $ex){
      Error::trigger("cartitem.add", [$ex->getMessage()]) ;
    }
  }

  function change(array $data, $cart_item_id){

    $cartItem = static::find($cart_item_id);

    try{
      return $cartItem->save();
    }
    catch(Exception $ex){
      Error::trigger("cartitem.change", [$ex->getMessage()]) ;
    }
  }

}
