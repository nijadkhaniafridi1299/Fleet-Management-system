<?php

namespace App\Model;

use App\Model;
use App\Message\Error;

class FleetRequest extends Model
{
	//use Validator;

	protected $primaryKey = "id";
	protected $table = "fm_request_logs";
	protected $fillable = ['user_id', 'body', 'fname', 'controller','device_type','api_ver'];
	public $timestamps = true;

	function user() {
		return $this->belongsTo('\App\Model\User', 'user_id', 'user_id');
	}

	public function add(array $data){
		$request = new FleetRequest();
		$request->user_id = $data['user_id'];
		$request->body = $data['body'];
		$request->fname = $data['fname'];
		$request->controller = $data['controller'];

		try {
			$request->save();
			return $request;
		}
		catch(Exception $ex) {
			Error::trigger("fleetrequest.add", [$ex->getMessage()]);
			return false;
		}
  	}
}
