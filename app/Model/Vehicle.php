<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Vehicle as Validator;

class Vehicle extends Model
{
    use Validator;

    protected $primaryKey = "vehicle_id";
    protected $table = "vehicles";
    public $timestamps = false;
    protected $fillable = [
        'vehicle_type_id',
        'asset_id',
        'vehicle_code',
        'vehicle_plate_number',
        'vehicle_category_id',
        'vehicle_valid_from',
        'vehicle_valid_to',
        'vehicle_opening_odometer',
        'engine_hours_type_id',
        'engine_hours',
        'geofence_id',
        'owner',
        'status',
        'status_description',
        'icon',
        'movement_detection',
        'min_speed',
        'min_stop',
        'min_parking',
        'min_sat',
        'max_distance_between_messages',
        'min_trip_time',
        'min_trip_distance',
        'business_type',
        'measurement_type',
        'max_capacity',
        'vin',
        'model',
        'year',
        'seats',
        'fuel_type',
        'start_operation',
        'contract_mileage',
        'opening_mileage',
        'job_order',
        'fuel_by_rate',
        'fuel__by_math',
        'device_id',
        'driver_id',
        'trailer_id',
        'current_latitude',
        'current_longitude',
        'vehicle_meta',
        'created_by',
        'sal_off_id',
        'company_id',
        'barcode',
        'helper_id',
        'store_id'

    ];
    protected $attributes = ['vehicle_meta'=>'{}', 'status' => 1, 'vehicle_category_id' => 0, 'vehicle_type_id' => 0, 'created_by'=>0];
    protected $hidden = ["created_at","updated_at","deleted_at"];

    function vehicleStatus() {
        return $this->belongsTo('App\Model\Status', 'status_id', 'status_id');
    }

    function user() {
        return $this->belongsTo('App\Model\User', 'driver_id', 'user_id');
    }

    // function userDriver() {
    //     return $this->belongsTo('App\Model\Driver', 'driver_id', 'driver_id');
    // }

    function vehicle_groups() {
        return $this->hasMany('App\Model\VehiclesInVehicleGroup', 'vehicle_id', 'vehicle_id');
    }

    function services() {
        return $this->hasMany('App\Model\Service', 'vehicle_id', 'vehicle_id');
    }

    function device() {
        return $this->hasOne('App\Model\Device', 'device_id', 'device_id');
    }

    function commands() {
        return $this->hasMany('App\Model\Command', 'vehicle_id', 'vehicle_id');
    }

    function driver_behaviors() {
        return $this->hasMany('App\Model\DriverBehavior', 'vehicle_id', 'vehicle_id');
    }

    function vehicle_type() {
        return $this->belongsTo('App\Model\VehicleType', 'vehicle_type_id', 'vehicle_type_id');
    }

    function vehicle_category(){
        return $this->belongsTo('App\Model\VehicleCategory', 'vehicle_category_id', 'vehicle_category_id');
    }

    function driver() {
        return $this->belongsTo('App\Model\User', 'driver_id', 'user_id');
    }

    function helper(){
        return $this->belongsTo('App\Model\User', 'helper_id', 'user_id');
    }

    function store() {
        return $this->belongsTo('App\Model\Store', 'store_id', 'store_id');
    }

    function delivery_trips() {
        return $this->hasMany('App\Model\DeliveryTrip', 'vehicle_id', 'vehicle_id');
    }
    function asset_inventory() {
        return $this->hasOne('App\Model\AssetInventory', 'assignee_id', 'vehicle_id');                                      
    }
    function current_trip() {
        return $this->delivery_trips()->whereIn("trip_status_id",[1,2])->where("status","!=",9)->orderBy("created_at","ASC");
    }

    function add($data) {

        // $data["vehicle_code"] = parent::generateModelCode($data['store_id'], 'VEH');
        if(!(isset($data["vehicle_code"]) && !empty($data["vehicle_code"])) && !empty($data["vehicle_plate_number"])){
            $data["vehicle_code"] = $data['store_id']."_".$data["vehicle_plate_number"];
        }

        if (!isset($data['speed'])) {
            $data['speed']['avg'] = "40";
            $data['speed']['max'] = "120";
        }

        $data['speed'] = array_filter($data['speed']);
        $data['speed'] = json_encode($data['speed'], JSON_UNESCAPED_UNICODE);
        
        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("vehicle.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $vehicle_id){

        $vehicle = Vehicle::find($vehicle_id);

        if (isset($data['vehicle_plate_number']) && $data['vehicle_plate_number'] == $vehicle->vehicle_plate_number) {
            unset($data['vehicle_plate_number']);
        }

        if (isset($data['vehicle_code']) && $data['vehicle_code'] == $vehicle->vehicle_code) {
            unset($data['vehicle_code']);
        }

        if (!isset($data['speed'])) {
            $data['speed']['avg'] = "40";
            $data['speed']['max'] = "120";
        }
        
        $data['speed'] = array_filter($data['speed']);
        $data['speed'] = json_encode($data['speed'], JSON_UNESCAPED_UNICODE);

        try{
            return parent::change($data, $vehicle_id);
        }
        catch(Exception $ex){
            Error::trigger("vehicle.change", [$ex->getMessage()]) ;
        }
    }
}
