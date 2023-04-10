<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Address as Validator;
use DB;

class Address extends Model{

    use Validator;
    protected $primaryKey = "address_id";
    protected $table = "addresses";
    protected $fillable = ['address', 'address_title', 'location_id', 'latitude', 'longitude', 'status', 'created_by', 'customer_id', 'type','erp_id'];
    protected $attributes = [];
    // protected static $columns = [
    //     "created_at" => "Created Date",
    //     "address_detail" => "Address Detail",
    //     "address_title" => "Address Title",
    //     "location_name" => "Location",
    //     "city_name" => "City",
    //     "latitude" => "Latitude",
    //     "longitude" => "Longitude",
    //     "status" => "Status"
    // ];

    public static function getTableColumns() {
        return self::$columns;
    }
    function location(){
        return $this->belongsTo('App\Model\Location', 'location_id');
    }

    function orders() {
        return $this->hasMany('App\Model\Order', 'address_id', 'shipping_address_id');
    }

    function delivery_trips() {
        return $this->hasManyThrough('App\Model\DeliveryTrip', 'App\Model\Order' ,'pickup_address_id','order_id', 'address_id','order_id');
    }


    public function pickup_material(){
        return $this->hasManyThrough('App\Model\DeliveryTrip', 'App\Model\Order' ,'pickup_address_id','order_id', 'address_id','order_id')
                                    ->join('pickup_materials','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')->select('delivery_trips.*');
    }

    public function type() {
        return $this->belongsTo('App\Model\AddressType', 'type', 'address_type_id');
    }    

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

    public function NewAddress(array $parm)
    {
      $address =  $this->add($parm);
      if(!is_object($address)){
        $errors[] = Error::get('address.add');
        return false;
      }
      else {
        return $address;
      }
    }

}
