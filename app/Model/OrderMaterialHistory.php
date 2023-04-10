<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\OrderMaterialHistory as Validator;

class OrderMaterialHistory extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "order_material_history";
    protected $fillable = ['order_id', 'user_id', 'material_id', 'weight','unit','remarks','value','length','status'];
    public $timestamps = true;

    function maintainMaterialHistory($data, $order_id){
      foreach($data['selected_material'] as $item){
        $material_id = Material::where('name', 'like', '%' . $item['label'] . '%')->value('material_id');
        if($material_id == null){
          $material_id = Material::insertGetId(['name' => $item['label']]);
        }
        OrderMaterialHistory::create(['order_id' => $order_id,
        'material_id' => isset($material_id) ? $material_id : null,
        'weight' => isset($item['weight']) ? $item['weight'] : null,
        'value' => isset($item['value']) ? $item['value'] : null,
        'unit' => isset($item['unit']) ? $item['unit'] : null , 
        'remarks' => isset($item['remarks']) ? $item['remarks'] : null,
        'status' => 1
        ]);
      
      }
    }
     
      

}
