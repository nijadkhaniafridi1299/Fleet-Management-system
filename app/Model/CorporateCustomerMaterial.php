<?php

namespace App\Model;

use App\Model;
use App\Validator\CorporateCustomerMaterial as Validator;

class CorporateCustomerMaterial extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "corporate_customer_material";
    protected $fillable = ['corporate_cust_address_id','parent_material_id','child_material_code','child_material_desc','status','created_at','updated_at'];
    protected $hidden = ["deleted_at"];

    // function add($data) {
    //     try {
    //         return parent::add($data);
    //     }
    //     catch(\Exception $ex){
    //         Error::trigger("sapapi.add", [$ex->getMessage()]);
    //     }
    // }

}