<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\DropoffMaterial as Validator;

class DropoffMaterial extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "dropoff_materials";
    protected $fillable = [
        'trip_id',
        'material_id',
        'weight',
        'unit',
        'e_ticket',
        'gate_pass',
        'status'
    ];
    protected $attributes = ['status' => 1];
    public $timestamps = true;

    function material(){
        return $this->belongsTo('App\Model\Material', 'material_id','material_id')->select(['material_id','name']);
    }

    function dropoff_unit(){
        return $this->belongsTo('App\Model\Unit', 'unit','id')->select(['id','unit']);
    }
}
