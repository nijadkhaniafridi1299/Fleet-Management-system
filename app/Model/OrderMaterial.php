<?php

namespace App\Model;

use App\Model;
use App\Model\Material;
use App\Model\Unit;
use App\Validator\OrderMaterial as Validator;
use DB;
use Auth;

class OrderMaterial extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "order_material";
    protected $fillable = ['order_id','material_id','weight','unit','status','remarks','value','length','customer_lot_id','skip_id'];

    function material(){
        return $this->belongsTo('App\Model\Material', 'material_id','material_id')->select(['material_id','name']);
    }

    function material_unit(){
        return $this->belongsTo('App\Model\Unit', 'unit', 'id');
    }
    public function skips(){
        return $this->belongsTo('App\Model\Skip' ,'skip_id','skip_id');
    }
  

}
