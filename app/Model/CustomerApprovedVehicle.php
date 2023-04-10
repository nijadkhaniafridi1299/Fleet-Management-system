<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CustomerApprovedVehicle as Validator;

class CustomerApprovedVehicle extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "customer_approved_vehicles";
    protected $fillable = [
        'customer_ud',
        'vehicle_id',
        'approved',
        'expiry_date',
    ];

    protected $attributes = ['status' => 1, 'created_by'=>0];

    function customer() {
        return $this->belongsTo('App\Model\Customer', 'customer_id', 'customer_id');
    }

    function vehicle() {
        return $this->belongsTo('App\Model\Vehicle', 'vehicle_id', 'vehicle_id');
    }

    function delivery_trips() {
        return $this->hasMany('App\Model\DeliveryTrip', 'vehicle_id', 'vehicle_id');
    }

    function add($data) {
        
        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("customerapprovedvehicle.add", [$ex->getMessage()]);
        }
    }
}
