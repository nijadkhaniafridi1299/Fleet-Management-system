<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\RecurringOrder as Validator;

class RecurringOrder extends Model
{
  use Validator;

  protected $primaryKey = "id";
  protected $table = "recurring_orders";
  protected $fillable = [
    'customer_id',
    'order_id',
    'when_num',
    'when_type',
    'day_of_week',
    'day_of_month',
    'expire_date',
    'status',
    'last_orderDate',
    'next_deliveryDate'
  ];
  public $timestamps = true;

  function add($data){
    $data['customer_id'] = (string) $data['customer_id'];
    $data['order_id'] = (string) $data['order_id'];
    $data['when_num'] = (int) $data['when_num'];
    $data['when_type'] = (int) $data['when_type'];
    $data['day_of_week'] = (string)$data['day_of_week'];
    $data['day_of_month'] = (int) $data['day_of_month'];
    $data['discount'] = (int) $data['discount'];
    $data['expire_date'] = (string) $data['expire_date'];
    $data['status'] = (int) $data['status'];

    try{
      $recurring_order = parent::add($data);
    }
    catch(\Exception $ex){
      Error::trigger("recurring_order.add", [$ex->getMessage()]) ;
    }
  }

  function change(array $data, $id){

    $recurring_order = static::find($id);

    $recurring_order->customer_id = (string) $data['customer_id'];
    $recurring_order->order_id = (string) $data['order_id'];
    $recurring_order->when_num = (int) $data['when_num'];
    $recurring_order->when_type = (int) $data['when_type'];
    $recurring_order->day_of_week = (string) $data['day_of_week'];
    $recurring_order->day_of_month = (int) $data['day_of_month'];
    $recurring_order->discount = (int) $data['discount'];
    $recurring_order->expire_date = (string) $data['expire_date'];
    $recurring_order->status = (int) $data['status'];

    try{
      $recurring_order->save();
    }
    catch(\Exception $ex){
      Error::trigger("recurring_order.change", [$ex->getMessage()]) ;
    }

  }

}
