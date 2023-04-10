<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Store as Validator;
use DB;

class Store extends Model{

    use Validator;

    protected $primaryKey = "store_type_id";
    protected $table = "store_types";
    protected $fillable = ['store_type', 'status', 'store_meta', 'created_by', 'company_id'];
    protected $attributes = ['store_meta'=>'[]', 'status'=>1];
    

    protected static $columns = [
        "store_type" => "Store Type",
        "status" => "Status"
    ];

    public static function getTableColumns() {
        return self::$columns;
    }

    function store(){
        return $this->belongsTo('App\Model\Store', 'store_type_id');
    }

}
