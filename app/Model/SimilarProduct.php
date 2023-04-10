<?php

namespace App\Model;

use App\Model;
use App\Message\Error;

class SimilarProduct extends Model
{
  // use Validator;



  protected $primaryKey = "id";
  protected $table = "similar_products";
  protected $fillable = ['product_id','product_name','sort'];
  // protected $attributes = ["status"=>1];
  public $timestamps = true;


  function product(){
    return $this->belongsTo('App\Model\Product', 'product_id','product_id');
  }

  function images(){
    return $this->hasMany('App\Model\ProductGallery', 'product_id','product_id')->orderBy('is_default','desc');
  }

  // function variants(){
  //   return $this->hasMany('App\Model\Variant', 'product_id','product_id')->where('status',1)->orderBy('sort','desc');
  // }


}
