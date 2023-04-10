<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Location as Validator;
use DB;

class Location extends Model{

    use Validator;
    protected $primaryKey = "location_id";
    protected $table = "locations";
    protected $fillable = ['parent_id', 'location_name', 'location_level_id', 'latitude', 'longitude', 'status', 'location_meta', 'created_by', 'key', 'short_code', 'vat', 'decimal_points'];
    protected $attributes = ['location_meta'=>'{}', 'parent_id' => 0, 'location_level_id'=>1/*, 'sap_id'=> 0,*/];

    protected static $columns = [
        "location_name" => "Area Name",
        "parent_name" => "City",
        "status" => "Status"
    ];

    public static function getTableColumns() {
        return self::$columns;
    }
   
    function parent(){
        return $this->belongsTo('App\Model\Location', 'parent_id', 'location_id');
    }


}
