<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\VehicleCategory as Validator;

class VehicleCategory extends Model
{
    use Validator;

    protected $primaryKey = "vehicle_category_id";
    protected $table = "vehicle_categories";
    protected $fillable = ['vehicle_category', 'vehicle_meta', 'created_by', 'status', 'company_id'];
    protected $attributes = ['vehicle_category_meta'=>'{}', 'status' => 1, 'created_by'=>0];

    protected static $columns = [
        "vehicle_category" => "Vehicle Category",
        "status" => "Status"
    ];
   

    
}
