<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Illuminate\Validation\Rule;
use App\Model\Store as Store;
use App\Model\TripBatches as TripBatches;
use App\Model\DeliveryTrip as DeliveryTrip;
class BatchController extends Controller

{




public function bactchListingAction(Request $request,$store_id)
{

   $parameterArray =  json_decode($request->get("data"),true);

    if($parameterArray != NULL || $parameterArray != null){
        
                $validator = Validator::make([
                    'fdate' => @$parameterArray['startDate'],
                    'todate' => @$parameterArray['endDate'],
                    'planedBy'=> @$parameterArray['planedBy'],
                    'executionType'=> @$parameterArray['executionType'],
                    'batch_no' => @$parameterArray['batch_no'],
                    'store_id' => $store_id
                ],[
                'fdate' => 'date|date_format:Y-m-d|nullable',
                'todate' => 'date|date_format:Y-m-d|nullable',
                'planedBy' => 'nullable|int|min:1',
                'executionType' => ['nullable',Rule::in(['SETLATER','NOW'])],
                'batch_no' => 'nullable|string|min:1',
                'store_id' => 'required|int|min:1|exists:stores,store_id'
            ]);
        }else{
            $validator = Validator::make(['store_id' => $store_id],[
            
            'store_id' => 'required|int|min:1'
        ]);
    }
    
    if ($validator->fails()) {
         return responseValidationError('Fields Validation Failed.', $validator->errors());
    }

    try{
        Store::where('store_id',$store_id);
        
    }
    catch(\Exception $ex){
        return response()->json([
            "code" => 500,
            "message" => "Couldn't find warehouse with your given id"
        ]);
    }
  


  
    
   $storeBatches = TripBatches::with(['batch_user']);
   if($parameterArray != null || $parameterArray !=NULL ){
    if(isset($parameterArray['planedBy']) && ($parameterArray['planedBy'] != '' || $parameterArray['planedBy'] != NULL || $parameterArray['planedBy'] != null)){
          
          $batchPlanBy = $parameterArray['planedBy'];
          $storeBatches = $storeBatches->where('created_by',$batchPlanBy);
         
        }


    //batch_type
    if(isset($parameterArray['executionType']) && ($parameterArray['executionType'] != '' || $parameterArray['executionType'] != NULL || $parameterArray['executionType'] != null)){
          
          $batchType = $parameterArray['executionType'];
          $storeBatches = $storeBatches->where('batch_type', $batchType);
        }    
   
    //start and end date    
    if(isset($parameterArray['startDate']) && ($parameterArray['startDate'] != '' || $parameterArray['startDate'] != NULL || $parameterArray['startDate'] != null) && isset($parameterArray['endDate']) && ($parameterArray['endDate'] != '' || $parameterArray['endDate'] != NULL || $parameterArray['endDate'] != null)){
          
          $from = $parameterArray['startDate'];
          $to = $parameterArray['endDate'];
          $storeBatches = $storeBatches->whereBetween(\DB::raw('DATE(plan_date)'), array($from, $to));
      }


    //batch_number
    if(isset($parameterArray['batch_no']) && ($parameterArray['batch_no'] != '' || $parameterArray['batch_no'] != NULL || $parameterArray['batch_no'] != null)){
          
          $batchNo = $parameterArray['batch_no'];
          $storeBatches = $storeBatches->where('batch_no', $batchNo);
        }   
   }
   

$deliverytripbatches = DeliveryTrip::get('batch_no')->toArray();
   $storeBatches = $storeBatches->where('store_id',$store_id)
   ->whereIn('batch_no',$deliverytripbatches)
   ->orderby('batch_id','ASC')->get();


   if($storeBatches->count()){
        $storeBatches = $storeBatches->toArray();
        $batchArray = array();
        foreach ($storeBatches as $key => $batch) {
         $userid=   $batch['created_by'];
    $username =  \DB::select("SELECT  u.first_name,u.last_name,u.name
from  users u
where u.user_id = $userid ");

$username = $username [0];
if(!isset($username->name)){

$temp_name = '{"en":"'.$username->first_name.' '.$username->last_name.'","ar":"'.$username->first_name.
    ' '.$username->last_name.'"}';
}
else{
$temp_name = $username->name;
}
                 
                 $batchArray[] = [
                     'batch_no' => $batch['batch_no'],
                     'planned_by' => json_decode($temp_name),
                     'no_of_trips' => $batch['no_of_trips'],
                     'no_of_vehicles' => $batch['no_of_vehicles'],
                     'total_orders' => $batch['total_orders'],
                     'plan_date' => ($batch['plan_date'] == NULL)?$batch['created_at']:$batch['plan_date'],
                     'execution_date' => ($batch['execution_date'] == NULL)?$batch['created_at']:$batch['execution_date'],
                     'execution_type' => $batch['batch_type'],
                     'batch_cost' => ($batch['batch_cost'] == NULL)?"0":$batch['batch_cost'],
                     'created_date' => $batch['created_at']  
                    ];
        }

        
        $response=[
            "code" => 200,
            "data" => [
                    "batches" => $batchArray,
                    "error" => []
                    ],
            "message" => "Batches Found!"
        ];
       
   }else{
    $response=[
            "code" => 204,
            "data" => [
                    "batches" => '',
                    "error" => []
                    ],
            "message" => "No Batches Found!"
        ];
   }

    return response()->json($response);

}


public function bactchDetailAction(Request $request,$store_id)
{
    $parameterArray =  json_decode($request->get("data"),true);
    if($parameterArray != NULL || $parameterArray != null){
        
                $validator = Validator::make([
                    
                    'batch_no' => $parameterArray['batch_no'],
                    'store_id' => $store_id
                ],[
                'batch_no' => 'nullable|string|min:1',
                'store_id' => 'required|int|min:1|exists:stores,store_id'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'code' => '300',
                'message' => 'Query Parameters Missing!'
            ]);
        }
    
    if ($validator->fails()) {
         return responseValidationError('Fields Validation Failed.', $validator->errors());
    }

    try{
        Store::where('store_id',$store_id);
    }
    catch(\Exception $ex){
        return response()->json([
            "code" => 500,
            "message" => "Couldn't find warehouse with your given id"
        ]);
    }

    try{
    $storeBatch = TripBatches::where('batch_no',$parameterArray['batch_no'])->get();

        if(!$storeBatch->isEmpty()){
            $batch=$storeBatch->toArray();
            $userid=   $batch[0]['created_by'];


            $stores = Store::where('store_id', $store_id)->get()
            ->toArray();
          
            $username =  \DB::select("SELECT  u.first_name,u.last_name,u.name
    from  users u
    where u.user_id = $userid ");
   
    $username = $username [0];
    if(!isset($username->name)){
        
        $temp_name = '{"en":"'.$username->first_name.' '.$username->last_name.'","ar":"'.$username->first_name.
            ' '.$username->last_name.'"}';
    }
            $requestarray = [
                'sal_off_id' => json_decode($stores[0]['store_name']),
                'from_date' => $batch[0]['plan_date'],
                'to_date' => $batch[0]['plan_date'],
                'request_id' => $batch[0]['batch_id'],
                'max_dist' => $batch[0]['max_distance'],      
                'is_approved' => 1,
                'order_ids' => $batch[0]['total_orders'],
                'created_by' => json_decode($temp_name) ,
                'trip_date' => $batch[0]['plan_date'],
                'veh_list' => 1,
                'constraints' => json_decode($batch[0]['constraints']),
               
                

                
            ];
            $responsearray = [
                'batch_no' => $batch[0]['batch_no'],
                'gas_cost_all_trips' => $batch[0]['batch_gas_cost'],
                'gross_amount_all_trips' => $batch[0]['batch_cost'],
                'no_dropped_orders' => $batch[0]['dropped_orders'],
                'no_of_solution' => $batch[0]['no_of_solutions'],
                'no_total_orders' => $batch[0]['total_orders'],
                'number_of_vehicles' => $batch[0]['no_of_vehicles'],
                'number_of_trips' => $batch[0]['no_of_trips'],
                

                
            ];

            
                return response()->json([
                        'code' => '200',
                        "data" => [
                            "request" => $requestarray,
                            "response" => $responsearray,
                            ],
                        'message' => 'Batch Found!'
                    ]);
        }
        else{
                return response()->json([
                    "code" => 402,
                    "message" => "No Batch Found with the given parameter!"
                    ]);
        }
    }catch(\Exception $ex){
        return response()->json([
            "code" => 500,
            "message" => "Something went wrong on server!",
            "error" => $ex->getMessage()
        ]);
    }

}
}
