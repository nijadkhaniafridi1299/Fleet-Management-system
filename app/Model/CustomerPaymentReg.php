<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CustomerPaymentReg as Validator;
class CustomerPaymentReg extends Model
{
  use Validator;

  protected $primaryKey = "id";
  protected $table = "customer_payment_regs";
  protected $fillable = ['customer_id', 'payment_type','reg_id','card_no','status','brand'];
  protected $attributes = [];
  public $timestamps = true;



  function add($data){

    try{
      $customer = parent::add($data);
      return $customer;
    }
    catch(\PDOException $ex){
      Error::trigger("customer.payment.reg.add", [$ex->getMessage()]) ;
    }
  }

}
