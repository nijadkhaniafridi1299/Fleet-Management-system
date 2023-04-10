<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use  App\Model\Order;
use App\Model\ServiceCategory ;
use Auth;



class InventoryController extends Controller
{
   
// public function inventoryList(){
    
//     $sub_categories = ServiceCategory::whereNotNull('parent_id')->where('parent_id','<>',$asset_id)->get()->toArray();
  
  
//     return response()->json([
//       "code" => 200,
//       "data" => [
//         "approved_orders" => $approved_orders
//       ],
//   "message" => "Data fetched Successfully"
//   ]);
//   }

}