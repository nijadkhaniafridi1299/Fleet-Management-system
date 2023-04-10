<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use  App\Model\Order;
use  App\Model\OrderServiceRequest;
use  App\Model\AssetTransaction;
use  App\Model\AssetInventory;
use  App\Model\ServiceCategory;
use  App\Model\Skip;
use Validator;
use Auth;



class AssetTransactionController extends Controller
{

public function allocateItems(Request $request){
  
  
  $data = json_decode($request->getContent(),true);
  $validator = Validator::make($request->all(), [
    'order_id' => 'required|integer|exists:orders,order_id',
    'temp_assignment.*.sub_category' => 'required|integer|exists:service_category,service_category_id',
    'temp_assignment.*.material_id' => 'required|integer|exists:material,material_id',
    'temp_assignment.*.items.*.asset_id' => 'required|integer|exists:inv_assets,asset_id'
    
  ]);
  if ($validator->fails()) {
  return responseValidationError('Fields Validation Failed.', $validator->errors());
  }

  $temp_transactions = [];
  $receive_transactions = [];
  $assign_asset_ids = [];
  $receive_asset_ids = [];
  $order_status = getOrderStatus($data['order_id']);
  if($order_status != "ACCEPTED"){
    return response()->json([
      "code" => 403,
      "data" => "",
      "message" => __("Order with only Accepted status can proceed")
  ]);
  }
  // $sub_categories = [];
  // $luggers = ServiceCategory::where('key', 'like', '%' . 'LUGGER' . '%')->pluck('service_category_id')->toArray();
  // $customer_id = Order::where('order_id' , $data['order_id'])->value('customer_id');
  
  
  
  // foreach($data['transactions'] as $items){
  //     if(count($items['items']) > 0){
         
  //       foreach($items['items'] as $item){
  //           if(isset($item['item']['value']) &&(isset($item['status']) && $item['status'] != ""&& $item['status'] != null)){
   
  //               if($item['status'] == "receive"){
  //                 array_push($receive_asset_ids,
  //                 $item['item']['value']
  //               );
  //               array_push($receive_transactions,array
  //               (
  //                    "order_id" => $data['order_id'],
  //                    "asset_id" => $item['item']['value'],
  //                    "remarks" => isset($item['remarks']) ? $item['remarks'] : null,
  //                    "transaction_date" => date('Y-m-d H:i:s'),
  //                    "no_of_days" => $item['days'],
  //                    "transaction_type" => $item['status'],
  //                    'created_at' => \Carbon\Carbon::now()->toDateTimeString()
  //                  ) 
  //               );
  //               }else{
  //                 if(in_array($item['sub_category'] , $luggers)){
  //                   array_push($sub_categories , ['asset_id' => $item['item']['value'] , 'customer_id' => $customer_id]);
  //                 }
  //                 array_push($assign_asset_ids,
  //                 $item['item']['value']
  //               );

  //               array_push($accept_transactions,array
  //               (
  //                    "order_id" => $data['order_id'],
  //                    "asset_id" => $item['item']['value'],
  //                    "remarks" => isset($item['remarks']) ? $item['remarks'] : null,
  //                    "transaction_date" => $item['date_requested'],
  //                    "no_of_days" => $item['days'],
  //                    "transaction_type" => $item['status'],
  //                    'created_at' => \Carbon\Carbon::now()->toDateTimeString()
  //                  ) 
  //               );
  //               }
  //           }
          
               
  //       }
  //     }
  
  // }
  // Skip::insert($sub_categories);
  // if(count($accept_transactions) > 0){
  //   AssetTransaction::insert($accept_transactions);
  //   // Order::where('order_id',$data['order_id'])->update(['order_status_id' => 14]); //Status Updated to Ready For Pickup
  // }
  // if(count($receive_transactions) > 0){
  //   AssetTransaction::insert($receive_transactions);
  // }
  // AssetInventory::whereIn('asset_id',$assign_asset_ids)->update(['allocated' => 1]);
  // AssetInventory::whereIn('asset_id',$receive_asset_ids)->update(['allocated' => 0]);

  ##Temporary assignment of assets to order
  foreach($data['temp_assignment'] as $items){
    
    if(count($items['items']) > 0){
      foreach($items['items'] as $item){
        
          array_push($temp_transactions,
          $item['asset_id']
      
        );
       }
       OrderServiceRequest::where('material_id',$items['material_id'])->where('order_id',$data['order_id'])->update(['temp_assets' => $temp_transactions]);
       Order::where('order_id',$data['order_id'])->update(['ready_for_pickup' => 1]);
    }
     
      
  }  
  return response()->json([
      "Code" => 200,
      "data" => "",
      "message" => "Successful Transaction. Your assets are Ready for Pickup."

  ]);
  }


}