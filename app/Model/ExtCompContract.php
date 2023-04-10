<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ExtCompContract as Validator;


class ExtCompContract extends Model
{
	use Validator;

	protected $primaryKey = "id";
	protected $table = "ext_comp_contracts";

	protected $fillable = [
		'company_id',
		'customer_id',
		'mobile',
		'send_message',
		'status'
	];

	protected $attributes = [];

	public $timestamps = false;

	function company(){
		return $this->belongsTo('App\Model\ExtComp', 'company_id', 'id')->where('status',1);
	}

	function add($data){

		try{
			return parent::add($data);
		}
		catch(\Exception $ex){
			Error::trigger("order.add", [$ex->getMessage()]) ;
		}
	}

}
