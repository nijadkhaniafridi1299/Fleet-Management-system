<?php

namespace App\Model;

use DB;
use Carbon\Carbon;
use App\Model;
use App\Message\Error;
use App\Validator\OrderItem as Validator;

class OrderItem extends Model
{
    use Validator;
    protected $primaryKey = "order_item_id";
    protected $table = "order_items";
    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price', 'status', 'price', 'foc','is_approved','equipment_id','tool_id','vehicle_id','labor_id','approval_doc'];
    protected $attributes = ['status'=> 1, 'foc' => 0];
    public $timestamps = true;
    function order(){
        return $this->belongsTo('App\Model\Order', 'order_id');
    }
    function addListOfEquipments($equipments,$order_id){
        $listOfEquipment = OrderItem::where('order_id', $order_id)->pluck('equipment_id')->toArray();
        $equipments_to_add = array_diff($equipments,$listOfEquipment);
        $data_to_insert = [];

foreach ($equipments_to_add as $key => $value)
{
    array_push($data_to_insert, [
            'equipment_id' => $value,
            'product_id' => 1,
            'quantity' => 1,
            'price' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'order_id' => $order_id
    ]);
}
DB::table('order_items')->insert($data_to_insert);
        
    }
    function addListOfVehicles($vehicles,$order_id){
        $listOfVehicles = OrderItem::where('order_id', $order_id)->pluck('vehicle_id')->toArray();
        $vehicles_to_add = array_diff($vehicles,$listOfVehicles);
        $data_to_insert = [];

        foreach ($vehicles_to_add as $key => $value)
        {
            array_push($data_to_insert, [
                    'vehicle_id' => $value,
                    'product_id' => 1,
                    'quantity' => 1,
                    'price' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'order_id' => $order_id
            ]);
        }
        DB::table('order_items')->insert($data_to_insert);
    }
    function addListOfTools($tools,$order_id){
        $listOfTools = OrderItem::where('order_id', $order_id)->where('model',null)->pluck('tool_id')->toArray();
        $tools_to_add = array_diff($tools,$listOfTools);
        $data_to_insert = [];

        foreach ($tools_to_add as $key => $value)
        {
            array_push($data_to_insert, [
                    'tool_id' => $value,
                    'product_id' => 1,
                    'quantity' => 1,
                    'price' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'order_id' => $order_id
            ]);
        }
        DB::table('order_items')->insert($data_to_insert);
    }
    function addListOfLabor($labor,$order_id){
        $listOfLabor = OrderItem::where('order_id', $order_id)->pluck('labor_id')->toArray();
        $labor_to_add = array_diff($labor,$listOfLabor);
        $data_to_insert = [];

        foreach ($labor_to_add as $key => $value)
        {
            array_push($data_to_insert, [
                    'labor_id' => $value,
                    'product_id' => 1,
                    'quantity' => 1,
                    'price' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'order_id' => $order_id
            ]);
        }
        DB::table('order_items')->insert($data_to_insert);

    }
    function product(){
        return $this->belongsTo('App\Model\Product', 'product_id');
    }
    // function variant(){
    //     return $this->belongsTo('App\Model\Variant', 'variant_id');
    // }

    function productWithTrashed(){
        return $this->belongsTo('App\Model\Product', 'product_id')->withTrashed();
    }
}
