<?php

namespace App\Model;

use App\Model;
use App\Model\DeliverySlot;
use App\Message\Error;

class CustomerDeliverySlot extends Model
{
  protected $primaryKey = "id";
  protected $table = "customer_delivery_slots";
  protected $fillable = ['customer_id','delivery_slot_id','time_slots','created_at','updated_at','status'];
  protected $attributes = ["status" => 1];
  public $timestamps = true;


  public function getDeliveryData($first, $last, $step = '+1 day', $output_format = 'D, d M y', $route_id = 1,$channel_id = 0,$customer_id,$days_count = 1){
    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);
    $count = 0;
    $today = 0;
    $j = 0;

    if(date('D', $current) == date('D')){
      $today = 1;
    }
    label1:
    while( $current <= $last ) {
      $day = date('D', $current);
      $time_slots = static::join('delivery_slots','customer_delivery_slots.delivery_slot_id','=','delivery_slots.id')
      ->select('delivery_slots.day','customer_delivery_slots.time_slots','delivery_slots.max_orders')
      ->where('delivery_slots.channel_id',$channel_id)
      ->where('customer_delivery_slots.customer_id',$customer_id)
      ->where('delivery_slots.status',1)
      ->where(['delivery_slots.route_id'=>$route_id,'delivery_slots.day'=>$day])
      ->where('customer_delivery_slots.status',1)
      ->first();
      if($time_slots){
        $dates[$count]['days'] = date($output_format, $current);
        $dates[$count]['time'] = json_decode($time_slots->time_slots,true);
        $dates[$count]['max_order_count'] = $time_slots->max_orders;

        if($today == 1){
          foreach ($dates[$count]['time'] as $key => $value) {
            $time = explode(" -",$value);
            $st_time    =   strtotime($time[0]);
            $cur_time   =   strtotime(date('h:i A'));

            if($st_time < $cur_time)
            {
              unset($dates[$count]['time'][$key]);
            }

          }
          $dates[$count]['time'] = array_values($dates[$count]['time']);

          if(empty($dates[$count]['time']) )
          {
            unset($dates[$count]);
          }
          $today = 0;
        }
      }
      else{

      }
      $current = strtotime($step, $current);
      $count++;
    }
    $dates = array_values($dates);

    foreach($dates as $key => $slot){
      $order = \App\Model\Order::
      where('order_status_id','<>',[4,6])
      ->whereRaw("delivery_day LIKE '%{$slot['days']}%'")
      ->count();
      
      if($order >= $slot['max_order_count'] && $slot['max_order_count'] != 0){
        unset($dates[$key]);
      }

    }


    $difference = $days_count - count($dates);
    if($difference > 0){

      $first = (date("Y-m-d h:i:s",$current));
      $first = $first;
      $last = strtotime($first);
      $last = strtotime("+".$difference." day", $last);
      $last = date('Y-m-d h:i:s', $last);
      $last = strtotime($last);

      goto label1;

    }

    if(empty($dates)){
      $dates['days'] = '';
      $dates['time'] = '';
    }

    return $dates;
  }

}
