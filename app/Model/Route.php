<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Route as Validator;

class Route extends Model
{
    use Validator;

    
	protected $primaryKey = "route_id";
	protected $table = "routes";
	protected $fillable = [
		'route_code',
		'route_name',
		'store_id',
		'stores',
		'status',
		'route_meta',
		'created_by',
		'salesman_id',
		'vehicle',
		'presales_id',
		'helper_id',
        'geofence_locations',
		'route_type',
		'company_id'
	];
   protected $attributes = ['route_meta'=>'{}', 'status' => 1];

   protected static $columns = [
		"route_code" => "Route Code",
		"vehicle_plate_number" => "Vehicle",
		"presale_name" => "Presales",
		"areas" => "Areas",
		"status" => "Status"
	];

    public static function getTableColumns() {
		return self::$columns;
	}

	function store(){
		return $this->belongsTo('App\Model\Store', 'store_id');
	}

	function vehicle(){
		return $this->belongsTo('App\Model\Vehicle', 'vehicle_id');
	}
}
