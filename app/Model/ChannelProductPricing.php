<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ChannelProductPricing as Validator;

class ChannelProductPricing extends Model
{
    use Validator;

    protected $primaryKey = "row_id";
    protected $table = "channel_product_pricing";
    protected $fillable = ['channel_id','product_id', 'quantity', 'price', 'price_vat', 'prod_sort', 'status'];
    protected $attributes = ['status' => 1];
    public $timestamps = true;


    function product(){
        return $this->belongsTo('App\Model\Product', 'product_id', 'product_id');
    }
    function productAjax(){
        return $this->belongsTo('App\Model\Product', 'product_id', 'product_id')->where('status',1);
    }
    function category(){
        return $this->belongsTo('App\Model\Category', 'category_id');
    }
    function images(){
        return $this->hasMany('App\Model\ProductGallery', 'product_id', 'product_id')->orderBy('is_default');
    }
    // function variants(){
    //     return $this->hasMany('App\Model\Variant', 'channel_id', 'channel_id')->where('status',1)->orderBy('sort','desc');
    // }

    function add($data){

            $result['channel_id'] = (int) $data['channel_id'];
            $result['product_id'] = (int) $data['product_id'];
            $result['quantity'] = $data['quantity'];
            $result['price'] = (float) $data['price'];
            $result['price_vat'] = (float) $data['price_vat'];
            $result['prod_sort'] = (int) $data['prod_sort'];
            if(isset($data['status'])){
              $result['status'] = (int) $data['status'];
            }
            try{
                $channel_product = parent::add($result);
                return $channel_product;

            }
            catch(\Exception $ex){
                Error::trigger("channel_product.add", [$ex->getMessage()]) ;
            }
    }

    function change(array $data, $row_id){

        $channel_product = static::find($row_id);

        $channel_product->channel_id = (int) $data['channel_id'];
        $channel_product->product_id = (int) $data['product_id'];
        $channel_product->quantity = $data['quantity'];
        $channel_product->price = (float) $data['price'];
        $channel_product->price_vat = (float) $data['price_vat'];
        $channel_product->prod_sort = (int) $data['prod_sort'];
        if(isset($data['status'])){
          $channel_product->status = (int) $data['status'];
        }
        try{
          return $channel_product->save();
        }
        catch(Exception $ex){
            Error::trigger("channel_product.change", [$ex->getMessage()]) ;
        }

    }

}
