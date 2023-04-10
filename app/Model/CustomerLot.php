<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CustomerLot as Validator;

class CustomerLot extends Model
{
	use Validator;
	protected $primaryKey = 'customer_lot_id';
	protected $table = 'customer_lots';
	protected $fillable = ['lot_number','address_id','customer_id','status','created_at','updated_at'];
	public $timestamps = false;

	function address(){
		return $this->belongsTo('App\Model\Address', 'address_id');
	}
}