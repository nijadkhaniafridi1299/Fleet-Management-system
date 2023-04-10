<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CustomerWarehouse as Validator;
use DB;

class CustomerWarehouse extends Model{

    use Validator;
    protected $primaryKey = "id";
    protected $table = "customer_warehouses";
    protected $fillable = ['address_of', 'address_title', 'address_detail', 'address_other_detail', 'location_id', 'latitude', 'longitude', 'status', 'address_meta', 'created_by', 'customer_id', 'company_id'];
    protected $attributes = [];
    protected static $columns = [
        "created_at" => "Created Date",
        "address_detail" => "Address Detail",
        "address_title" => "Address Title",
        "location_name" => "Location",
        "city_name" => "City",
        "latitude" => "Latitude",
        "longitude" => "Longitude",
        "status" => "Status"
    ];

    // public static function getTableColumns() {
    //     return self::$columns;
    // }
    // function location(){
    //     return $this->belongsTo('App\Model\Location', 'location_id');
    // }

    // function orders() {
    //     return $this->hasMany('App\Model\Order', 'address_id', 'shipping_address_id');
    // }
    function add($data){

        if(!isset($data["status"])){
            $data["status"] = 1;
        }

        try{
            $address = parent::add($data);
            return $address;
        }
        catch(\Exception $ex){
            Error::trigger("address.add", [$ex->getMessage()]) ;
        }
    }

    function change(array $data, $address_id){

    

        if(!isset($data["status"])){
            $data["status"] = 1;
        }

        try {
           return parent::change($data, $address_id);
        }
        catch(Exception $ex){
            Error::trigger("address.change", [$ex->getMessage()]) ;
        }

    }

    // public function NewAddress(array $parm)
    // {
    //   $address =  $this->add($parm);
    //   if(!is_object($address)){
    //     $errors[] = Error::get('address.add');
    //     return false;
    //   }
    //   else {
    //     return $address;
    //   }
    // }

}
