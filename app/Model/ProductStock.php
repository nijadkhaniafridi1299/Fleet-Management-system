<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ProductStock as Validator;

class ProductStock extends Model
{
    use Validator;

    protected $primaryKey = "stock_id";
    protected $table = "product_stocks";
    protected $fillable = ['material','stock', 'capacity', 'min_stock_limit', 'refill_stock_limit', 'lap_days', 'status'];
    protected $attributes = ["status" => 1];
    public $timestamps = true;

    /*function __construct($attributes = []){
        if(!iiset($this->attributes["updated_at"])){
            $this->attributes['updated_at'] = date("Y-m-d H:i:s");
        }
        parent::__construct($attributes);
    }*/

////////////////////////////////////////////////////////////////////////////////////



    // function category(){
    //     return $this->belongsTo('App\Model\Category', 'category_id');
    // }
    //
    // function images(){
    //     return $this->hasMany('App\Model\ProductGallery', 'product_id')->orderBy('is_default');
    // }

    function product(){
      return $this->has('App\Model\Product');
    }

    function add($data){

            $data['store_id'] = (int) $data['store_id'];
            $data['material'] = $data['material'];
            $data['stock'] = (int)($data['stock']);

            $data['status'] = (int) $data['status'];

            try{
                $product = parent::add($data);

            }
            catch(\Exception $ex){

              //echo '<pre>'.print_r($data, true).'</pre>'; exit;
                Error::trigger("productStock.add", [$ex->getMessage()]) ;
            }
    }

//     function change(array $data, $product_id){
//
//
//         if(!isset($data["status"]) && isset($data["product_status"])){
//             $data["status"] = $data["product_status"];
//         }
//
//         $product = static::find($product_id);
//
//       //  echo __LINE__; exit;
//
//         //$category_name = json_decode($category->category_name, true);
//         //$category_description = json_decode($category->category_description, true);
//
//         $product->product_name = json_encode($data['product_name'], JSON_UNESCAPED_UNICODE);
//         $product->product_description = json_encode($data['product_description'], JSON_UNESCAPED_UNICODE);
//         //$product->product_image = json_encode($data['product_image'], JSON_UNESCAPED_UNICODE);
//         $product->material = json_encode($data['material'], JSON_UNESCAPED_UNICODE);
//
// //////////////////////////////////////////////////////////////////////////////////
//
//         // if(!isset($data["category_status"])){
//         //     $category->category_status = 1;
//         // }
//
//         $product->category_id = (int) $data['category_id'];
//         $product->price = (float) $data['price'];
//         $product->quantity = (int) $data['quantity'];
//         $product->unit = (int) $data['unit'];
//         $product->product_sort = (int) $data['product_sort'];
//         $product->product_status = (int) $data['product_status'];
//         $product->product_image = (string) $data['product_image'];
//
//
//
//         //echo '<pre>'. print_r($category->toArray(), true).'</pre>'; exit;
//
//         try{
//             $product->save();
//         }
//         catch(Exception $ex){
//             Error::trigger("product.change", [$ex->getMessage()]) ;
//         }
//
//
//     }

}
