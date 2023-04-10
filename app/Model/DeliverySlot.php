<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\DeliverySlot as Validator;
use DB;

class DeliverySlot extends Model
{
    use Validator;
    protected $primaryKey = "delivery_slot_id";
    protected $table = "delivery_slots";
    protected $fillable = ['delivery_slot_title', 'status', 'key', 'company_id'];
    protected $attributes = ["status" => 1];
    public $timestamps = true;
    protected static $columns = [
        "delivery_slot_title" => "Delivery Slot",
        "status" => "Status"
    ];

    public static function getTableColumns() {
        return self::$columns;
    }
 
    public function getSlots($first, $last, $step = '+1 day', $output_format = 'D, d M y', $route_id = 1, $channel_id = 0,$days_count = 1)
    {
  
  
      $dates = array();
  
      $current = strtotime($first);
      $last = strtotime($last);
      $count = 0;
      $today = 0;
      if(date('D', $current) == date('D')){
        $today = 1;
      }
      label1:
       while( $current <= $last ) {
        $day = date('D', $current);
        $time_slots = static::where(['route_id'=>$route_id,'day'=>$day,'status'=>1,'channel_id'=>$channel_id])->first();
        if($time_slots){
          $dates[$count]['days'] = date($output_format, $current);
          $dates[$count]['time'] = json_decode($time_slots->time_slots,true);
          $dates[$count]['max_order_count'] = $time_slots->max_orders;
          if($today == 1){
              // dd($dates[$count]['time']);
            foreach ($dates[$count]['time'] as $key => $value) {
              $time = explode(" -",$value);
              $st_time    =   strtotime($time[0]);
              // $end_time   =   strtotime('8 PM');
              $cur_time   =   strtotime(date('h:i A'));
              if($st_time < $cur_time)
              {
                // dd($st_time."cuurent time".$cur_time);
                unset($dates[$count]['time'][$key]);
              // dd($dates[$count]['time']);
  
                // dd('close');
              }
  
            }
            $dates[$count]['time'] = array_values($dates[$count]['time']);
  
            if(empty($dates[$count]['time']) )
            {
              unset($dates[$count]);
            }
              // dd($dates[$count]['time']);
  
            // dd($dates[$count]['time'],date('h A'));
            $today = 0;
          }
          // filter time slots on basis of available time slots
        }
        $current = strtotime($step, $current);
        $count++;
      }
      // dd('ss');
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
                // $first = $first;
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
