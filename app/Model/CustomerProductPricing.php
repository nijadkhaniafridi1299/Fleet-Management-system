<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CustomerProductPricing as Validator;

class CustomerProductPricing extends Model
{
  use Validator;

  protected $primaryKey = "row_id";
  protected $table = "customer_product_pricing";
  protected $fillable = ['customer_id','material_id', 'quantity', 'price', 'price_vat', 'prod_sort', 'status'];
  protected $attributes = ['status' => 1];
  public $timestamps = true;

//   function product(){
//     return $this->belongsTo('App\Model\Product', 'product_id', 'product_id');
//   }
//   function channel_product(){
//     return $this->belongsTo('App\Model\ChannelProductPricing', 'product_id', 'product_id');
//   }
//   function sub_channel_product(){
//     return $this->belongsTo('App\Model\ChannelProductPricing', 'product_id', 'product_id');
//   }
//   function productChange(){
//     return $this->belongsTo('App\Model\Product', 'product_id', 'product_id')->where('status',1);
//   }
//   function category(){
//     return $this->belongsTo('App\Model\Category', 'category_id');
//   }
//   function images(){
//     return $this->hasMany('App\Model\ProductGallery', 'product_id', 'product_id')->orderBy('is_default');
//   }
//   function variants(){
//     return $this->hasMany('App\Model\Variant', 'customer_id', 'customer_id')->where('status',1)->orderBy('sort','desc');
//   }

  function add($data){

    $sort = static::max('prod_sort');

    $result['customer_id'] = (int) $data['customer_id'];
    $result['product_id'] = (int) $data['product_id'];
    $result['quantity'] = $data['quantity'];
    $result['prod_sort'] = $sort+1;
    $result['price'] = (float) $data['price'];
    $result['price_vat'] = (float) $data['price_vat'];
    if(isset($data['status'])){
      $result['status'] = (int) $data['status'];
    }
    try{
      return parent::add($result);

    }
    catch(\Exception $ex){
      Error::trigger("customer_product.add", [$ex->getMessage()]) ;
    }
  }

  function change(array $data, $row_id){

    $customer_product = static::find($row_id);

    $customer_product->customer_id = (int) $data['customer_id'];
    $customer_product->product_id = (int) $data['product_id'];
    $customer_product->quantity = (int) $data['quantity'];
    $customer_product->price = (float) $data['price'];
    $customer_product->prod_sort = (int) $data['prod_sort'];
    if(isset($data['status'])){
      $customer_product->status = (int) $data['status'];
    }
    try{
      return $customer_product->save();
    }
    catch(Exception $ex){
      Error::trigger("customer_product.change", [$ex->getMessage()]) ;
    }

  }

}
