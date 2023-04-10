<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CustomerExtra as Validator;
use DB;
class CustomerExtra extends Model
{
  use Validator;

  protected $primaryKey = "id";
  protected $table = "customer_extras";
  protected $fillable = ['customer_id', 'fcm_token','tamayouz_guid'];
  protected $attributes = ['customer_id' => 0];
  public $timestamps = true;



  function add($data){

    $data['customer_id'] = (int) $data['customer_id'];

    if(isset($data['fcm_token'])){
      $data['fcm_token'] = (string) $data['fcm_token'];
    }

    try{
      $customer = parent::add($data);
      return $customer;
    }
    catch(\PDOException $ex){
      Error::trigger("customer.extra.add", [$ex->getMessage()]) ;
    }
  }

}
