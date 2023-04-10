<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\TripAssignedMaterial as Validator;
use DB;

class TripAssignedMaterial extends Model
{
    use Validator;
    protected $primaryKey = "id";
    protected $table = "trip_assigned_materials";
    public $timestamps = true;
    protected $fillable = 
    [
     'delivery_trip_id', 'material_id', 'unit', 'weight', 'skip_id', 'created_by', 'updated_by', 'deleted_at'
    ];
    protected $hidden = ["created_at","created_by","updated_by","updated_at","deleted_at"];
    
    function material(){
        return $this->belongsTo('App\Model\Material', 'material_id','material_id')->select(['material_id','name']);
    }

    function material_unit(){
        return $this->belongsTo('App\Model\Unit', 'unit', 'id')->select(['id','unit']);
    }
}