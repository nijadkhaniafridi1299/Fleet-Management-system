<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Category;
use App\Validator\Product as Validator;

class Product extends Model
{
    use Validator;

 
    protected $primaryKey = "product_id";
    protected $table = "products";
    protected $fillable = ['product_name', 'product_description', 'product_meta', 'created_by', 'status', 'product_images', 'price', 'company_id', 'product_sort'];
    protected $attributes = ['product_meta'=>'{}', 'product_images'=>'{}', 'status' => 1, "product_sort" => 0];

    protected static $columns = [ 
        "product_images" => "Product Image",
        "product_name" => "Product Name", 
        "product_description" => "Product Description",
        "categories" => "Categories",
        "price" => "Price",
        "volume" => "Volume",
        "weight" => "weight",
        "status" => "Status"
    ];

    public static $columnsForCSVdownload = [
 
        "product_name[en]" => ["Product Name (English)", true],
        "product_name[ar]" => ["Product Name (Arabic)", true],
        "product_description[en]" => ["Description (English)", true],
        "product_description[ar]" => ["Description (Arabic)", true],
        "price" => ["Price", true, "Mention in Number"],
        "categories" => ["Categories", true, "Comma separated names"],
        "product_sort" => ["Sort", false],
        "sku" => ["SKU", false], 
        "volume[length]" => ["Length", true, "Mention in Number"],
        "volume[width]" => ["Width", true, "Mention in Number"],
        "volume[height]" => ["Height", true, "Mention in Number"],
        "volume[unit]" => ["Unit of Volume", true, "mm/cm"],
        "weight" => ["Weight", true],
        "product_meta[weight_unit]" => ["Unit of Weight", false, "lb/kg"]
    ];

    public static function getTableColumns() {
        return self::$columns;
    }

    function category() {
        return $this->belongsTo('App\Model\Category', 'category_id', 'category_id');
    }

    function images() {
        return $this->hasMany('App\Model\ProductGallery', 'product_id', 'product_id');
    }
    
    function channels() {
        return $this->hasMany('App\Model\ChannelProductPricing', 'product_id', 'product_id')->where('status', 1);
    }
    
}
