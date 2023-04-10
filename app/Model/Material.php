<?php

namespace App\Model;

use App\Model;
use App\Validator\Material as Validator;
use DB;
use Auth;

class Material extends Model
{
    use Validator;

    protected $primaryKey = "material_id";
    protected $table = "material";
    protected $fillable = ['name','erp_id','default_unit','status','customer_id'];


    function customer_pricing(){
        return $this->belongsTo('App\Model\CustomerProductPricing', 'material_id', 'material_id');
    }

    function customers(){
        return $this->belongsTo('App\Model\Customer', 'customer_id');
    }
    function corporate_customer_material(){
        return $this->hasMany('App\Model\CorporateCustomerMaterial', 'parent_material_id','material_id');
    }
    function skips(){
        return $this->belongsTo('App\Model\Skip', 'material_id');
    }

}
