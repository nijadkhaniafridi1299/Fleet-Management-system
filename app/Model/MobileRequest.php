<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\HyperPay;
use App\Model\Customer;


class MobileRequest extends Model
{
  //use Validator;

  protected $primaryKey = "id";
  protected $table = "mobile_client_requests";
  protected $fillable = ['client_id', 'transaction_id' , 'body', 'fname', 'device_type', 'api_ver', 'record_status','mobile_brand','response'];
  public $timestamps = true;


  public function add(array $data){
    $request = new MobileRequest();
    $request->client_id = $data['client_id'];
    $request->body = $data['body'];
    $request->fname = $data['fname'];
    $request->device_type = $data['device_type'];
    $request->api_ver = $data['api_ver'];
    $request->record_status = $data['record_status'];
    if(isset($data['transaction_id'])){
      $request->transaction_id = $data['transaction_id'];
    }
    if(isset($data['mobile_brand'])){
      $request->mobile_brand = $data['mobile_brand'];
    }

    try{
      $request->save();
      return $request;
    }
    catch(Exception $ex){
      Error::trigger("request.add", [$ex->getMessage()]) ;
      return false;
    }
  }

}
