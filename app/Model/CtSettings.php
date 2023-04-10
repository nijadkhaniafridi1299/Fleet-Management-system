<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use Carbon\Carbon;

class CtSettings extends Model
{
    
    public $timestamps = false;
    protected $primaryKey = "id";
    protected $table = "ct_settings";
    protected $fillable = [
        'settingName',
        'store_id',
        'inputType',
        'desc',
        'valuesConstraint',
        'currentValue'

    ];


    function delivery_trip(){
        return $this->belongsTo('App\Model\DeliveryTrip', 'delivery_trip_id');
    }

    function order(){
        return $this->belongsTo('App\Model\Order', 'order_id');
    }

    function  add($data){

        try{
            return  parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("StoreConstraints.add", [$ex->getMessage()]) ;
        }
    }

    function change(array $data, $delivery_id){

        try{
            parent::change($data, $delivery_id);
        }
        catch(Exception $ex){
            Error::trigger("StoreConstraints.change", [$ex->getMessage()]) ;
        }

    }



}
