<?php

namespace App;

use Illuminate\Database\Eloquent\Model as CoreModel;
use App\Validator;
use App\Message\Error;
use Illuminate\Database\Eloquent\SoftDeletes;

class Model extends CoreModel{

	use Validator, SoftDeletes;

	function __construct(array $attributes = []){
		parent::__construct($attributes);
	}

	function add(array $data){

		$entity = substr(\get_class($this), strrpos(get_class($this), "\\")+ 1);

		if($this->validate($data)){

			/*
			$model = static::create($data);
			return $model;
			*/

			$class = get_class($this);

			$model = new $class();
			foreach($data as $field => $value){
				$model->{$field} = $value;
			}

			try{

				$model->save();
				return $model;
			}
			catch(\Exception $ex){
				Error::trigger( strtolower($entity). '.add', ['pdo' => $ex->getMessage()]);
				return [];
			}
		}
		else {
			Error::trigger( strtolower($entity) . '.add', $this->getErrors());
		}
	}

	function change(array $data, $id){

		$entity = substr(\get_class($this), strrpos(get_class($this), "\\")+ 1);

		if ($this->validate($data)) {

			$model = static::find($id);

			foreach($data as $field => $value){
				$model->{$field} = $value;
			}

			try{
				$model->save();
				return $model;
			}
			catch(\Exception $ex){
				Error::trigger( strtolower($entity) . '.change', ['pdo' =>$ex->getMessage()]);
				return [];
			}
		}
		else {
			Error::trigger( strtolower($entity) . '.change', $this->getErrors());
		}
	}

	function remove($id){
		$model = static::find($id);

		try {
			$model->delete();
		} catch (\Exception $ex) {
			Error::trigger( strtolower($entity) . '.remove', ['pdo' =>$ex->getMessage()]);
		}
		//static::where($this->primaryKey, $id);
	}

	function __toString(){
		return '<pre>'.print_r($this->attributes, true).'</pre>';
	}

	function batchUpdate($data,$index){
		try {
		$entity = substr(\get_class($this), strrpos(get_class($this), "\\")+ 1);
		$calss = get_class($this);
		$userInstance = new $calss;
		$result = batch()->update($userInstance, $data, $index);
		return $result;
		} catch (\Exception $e) {
		Error::trigger( strtolower($entity) . '.bulk.update', [$ex->getMessage()]);
		return false;
		}

	}
	function generateRandomNumber($min = 0, $max = 999999999){

		$string = "{$max}";

		$random = rand($min, $max);

		return str_pad($random, strlen($string), 0, STR_PAD_LEFT);
	}

	function generateCode($column, $prefix){

		do{
			$code = $prefix . $this->generateRandomNumber();
		} while(static::where($column, $code)->count() > 0);

		return $code;
	}

	function generateModelCode($str, $prefix) {
		$last_id = 0;
		$latest = static::latest('created_at')->withTrashed()->first();
		if (is_object($latest)) {
			$last_id = $latest[$this->primaryKey];
		}

		$last_id = (int) $last_id + 1;
		$code = $str . $last_id;
		$code = str_pad($code, 8, "0", STR_PAD_LEFT);
		
		return $prefix . $code;
	}
}
