<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\AssetInventory as Validator;

class AssetInventory extends Model
{
    use Validator;

    protected $primaryKey = "asset_id";
    protected $table = "inv_assets";
    public $timestamps = false;
    protected $fillable = [
        'title',
        'vehicle_id',
        'allocated',
        'assigned_to',
        'assignee_id',
        'service_category_id'
    ];

    // protected $attributes = ['status' => 1];

    function transactions() {
        return $this->hasOne('App\Model\AssetTransaction', 'asset_id', 'asset_id')->orderBy('created_at','desc');
    }

    function service_category() {
        return $this->belongsTo('App\Model\ServiceCategory','service_category_id','service_category_id')->select('service_category_id','title','key');
    }

    function yard(){
        return $this->belongsTo('App\Model\Store', 'assignee_id', 'store_id')->select('store_id','store_name','address','latitude','longitude');                                      
    }

    function vehicles(){
        return $this->belongsTo('App\Model\Vehicle', 'assignee_id', 'vehicle_id')->select('vehicle_id','vehicle_plate_number');                                      
    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("AssetInventory.add", [$ex->getMessage()]);
        }
    }

}
