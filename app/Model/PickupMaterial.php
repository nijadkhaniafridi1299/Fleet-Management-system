<?php

namespace App\Model;

use App\Model;
use DB;
use Auth;
use App\Message\Error;
use App\Validator\PickupMaterial as Validator;

class PickupMaterial extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "pickup_materials";
   

    function material(){
        return $this->belongsTo('App\Model\Material', 'material_id','material_id')->select(['material_id','name']);
    }

    function material_unit(){
        return $this->belongsTo('App\Model\Unit', 'unit', 'id')->select(['id','unit']);
    }

    function pickup_unit(){
        return $this->belongsTo('App\Model\Unit', 'unit','id')->select(['id','unit']);
    }
    
    protected $fillable = [
        'trip_id',
        'material_id',
        'weight',
        'unit',
        'e_ticket',
        'gate_pass',
        'skip_id',
        'asset_id',
        'status'
    ];
    protected $attributes = ['status' => 1];
    public $timestamps = true;
}
