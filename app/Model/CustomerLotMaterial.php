<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CustomerLotMaterial as Validator;

class CustomerLotMaterial extends Model
{
	use Validator;
	protected $primaryKey = 'lot_material_id';
	protected $table = 'customer_lot_materials';
	protected $fillable = ['customer_lot_id','material_id','quantity','status','created_at','updated_at'];
	public $timestamps = false;
}