<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Skip;
use App\Model\Customer;
use App\Model\TransactionType;
use Validator;
use Auth;
use DB;


class SkipController extends Controller

{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
    * @OA\Get(
    *   path="/skips",
    *   summary="Return the list of skips installed on site locations",
    *   tags={"skips"},
    *    @OA\Response(
    *      response=200,
    *      description="List of skip/luggers",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of skips/luggers",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */

    public function index(Request $request) {
        $per_page = $request->get("perPage");
        $per_page = isset($per_page) && is_numeric($per_page) ? $per_page : 10; 
        $data =  json_decode($request->get("data"),true);
        $assigned_to_vehicle = getAssetTransactionSource('VEHICLE');
        $assigned_to_yard = getAssetTransactionSource('YARD');
        $assigned_to_cust = [];
        
        $skips = Skip::with('address:address_id,address_title,address,latitude,longitude',
                            'customer:customer_id,name','material:material_id,name', 
                            'current_skip_level:skip_level_id,skip_level,color',
                            'asset_inventory:asset_id,title,asset_id,assigned_to')
                   
                            ->with(['asset_inventory.vehicles' => function ($q) use($assigned_to_vehicle) {
                                $q->whereHas('asset_inventory', function($q) use($assigned_to_vehicle) {
                                    $q->where('inv_assets.assigned_to', $assigned_to_vehicle);
                                });
                            }])
                            ->with(['asset_inventory.yard' => function ($q) use($assigned_to_yard) {
                                $q->whereHas('asset_inventory', function($q) use($assigned_to_yard) {
                                    $q->where('inv_assets.assigned_to', $assigned_to_yard);
                                });
                            }]);
        if(!Auth::guard('oms')->check()){
            $user = (Auth::user());
            $user_id = ($user->user_id);
            // $assigned_to_cust = Customer::where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id)->get()->toArray();
            if(checkIfAdmin($user_id) == false){
                
                $skips->doesntHave('customer')->orWhereHas('customer', function($query) use($user_id){
                    $query->where('project_manager_id',$user_id);
                    });
            }
           
        }
                    
        // if(count($assigned_to_cust) > 0){
                              
        // }
        
        if (isset($data['customer_id']) && $data['customer_id'] != null) {
            $skips->where('customer_id', $data['customer_id']);
        }

        if (isset($data['material_id']) && $data['material_id'] != null) {
            $skips->where('material_id', $data['material_id']);
        }

        if (isset($data['address_id']) && $data['address_id'] != null) {
            $skips->where('address_id', $data['address_id']);
           
        }


        if(isset($data['yard_id']) && $data['yard_id'] != ""){
            $yard_id = $data['yard_id'];
            $skips->whereHas('asset_inventory', function($query) use($yard_id){
                $query->whereHas('yard', function ($query) use($yard_id){
                    $query->where('store_id', $yard_id);
                });
            });
//  \DB::enableQueryLog();
//             dd(\DB::getQueryLog(),$skips);
        }

        if(isset($data['vehicle_id']) && $data['vehicle_id'] != ""){
            $vehicle_id = $data['vehicle_id'];
            $skips->whereHas('asset_inventory', function($query) use($vehicle_id){
                $query->whereHas('vehicles', function ($query) use($vehicle_id){
                    $query->where('vehicle_id', $vehicle_id);
                });
            });

        }

        if (isset($data['skip_level_id']) && $data['skip_level_id'] > 0) {
            $skips->where('current_skip_level_id', $data['skip_level_id']);
        }

        if (isset($data['from']) && $data['from'] != '' && isset($data['to']) && $data['to'] != '' ) {
            $from = $data['from'] . ' 00:00:00';
            $to = $data['to'] . ' 23:59:59';
            $skips->whereBetween('created_at', [$from, $to]);
        }

        
        if(isset($data['paginate']) && ($data['paginate'] == true || $data['paginate'] == 1)){

            $skips = $skips->orderBy('created_at','DESC')->paginate($per_page)->toArray();
            foreach($skips['data'] as &$skip){
           
                $source_vehicle_id = getAssetTransactionSource('VEHICLE');
                $source_yard_id = getAssetTransactionSource('YARD');
                $source_customer_id = getAssetTransactionSource('CUSTOMER');
                if(isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_vehicle_id){
                    $skip['transaction_types'] = \App\Model\TransactionType::whereIn('key',['RECEIVE','TRANSFER_TO_CLIENT'])->get(['name as label','key as key_value'])->toArray();
                    
                    

                   
                }
                else if(isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_yard_id){
                    
                    $skip['transaction_types']= TransactionType::where('key','ASSIGN')->get(['name as label','key as key_value'])->toArray();
                    
                   
                   
                }
                else if(isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_customer_id){
                    
                    $skip['transaction_types']= TransactionType::where('key','TRANSFER_FROM_CLIENT')->get(['name as label','key as key_value'])->toArray();
                  
                    
                }else{
                    $skip['transaction_types'] = [];
                }
            }
        }else{
            $skips = $skips->orderBy('created_at','DESC')->get(['skip_id','customer_id','skip_password','imei',
            'connection_state','sim_card_number','asset_id','material_id','current_level','current_skip_level_id','address_id'])->toArray();
            foreach($skips as &$skip){
           
                $source_vehicle_id = getAssetTransactionSource('VEHICLE');
                $source_yard_id = getAssetTransactionSource('YARD');
                $source_customer_id = getAssetTransactionSource('CUSTOMER');
                if(isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_vehicle_id){
                    $skip['transaction_types'] = \App\Model\TransactionType::whereIn('key',['RECEIVE','TRANSFER_TO_CLIENT'])->get(['name as label','key as key_value'])->toArray();
                  
                }
                else if(isset($skip['asset_inventory']) && isset($skip['asset_inventory']) != null && isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] == $source_yard_id){
                    $skip['transaction_types'] = \App\Model\TransactionType::whereIn('key',['ASSIGN'])->get(['name as label','key as key_value'])->toArray();

                }
                else if(isset($skip['asset_inventory']) && isset($skip['asset_inventory']) != null && isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_customer_id){
                    $skip['transaction_types'] = \App\Model\TransactionType::whereIn('key',['TRANSFER_FROM_CLIENT'])->get(['name as label','key as key_value'])->toArray();
              
                }else{
                    $skip['transaction_types'] = [];
                }
            }
        }
        //send filter criteria
        if(count($assigned_to_cust) > 0){
            $customers = \App\Model\Customer::where('project_manager_id',$user_id)->orderBy('created_at','DESC')->get()->toArray();
        }else{
            $customers = \App\Model\Customer::orderBy('created_at','DESC')->get()->toArray();
        }
        $skip_levels = \App\Model\SkipLevel::withCount('skips')->orderBy('created_at','DESC')->get()->toArray();
        $yards = \App\Model\Store::where('status',1)->get(['store_id','store_name','latitude','longitude','address'])->toArray();
        $vehicle_type = getVehicleTypeId('HOOK_TRUCK');
        $hook_trucks = \App\Model\Vehicle::get(['vehicle_id','asset_id','vehicle_code','vehicle_plate_number','vehicle_category_id'])->toArray();
        return response()->json([
            "code" => 200,
            "skips" => $skips,
            "customers" => $customers,
            "yards" => $yards,
            "hook_trucks" => $hook_trucks,
            "skip_levels" => $skip_levels
        ]);
    }

    public function getCustomerSkips(Request $request) {
	    $data =  json_decode($request->get("data"), true);

		if (!isset($data['customer_id']) || empty($data['customer_id'])) {
			return response()->json([
				"code" => 403,
				"data" => "",
				"message" => "Missing Input."
			]);
		}

		$user = auth()->guard('oms')->user();
       
		if ($user->customer_id != $data['customer_id']) {
			return response()->json([
				"code" => 403,
				"data" => "",
				"message" => "Unauthorized User."
			]);
  	    }

        $skips = Skip::with('current_skip_level:skip_level_id,skip_level,color', 'asset_inventory:asset_id,title','material:material_id,name')->where('customer_id', $data['customer_id']);
        
        if (isset($data['address_id']) && $data['address_id'] > 0) {
            $skips->where('address_id', $data['address_id']);
        }

        $skips = $skips->get()->toArray();

        return response()->json([
            "code" => 200,
            "skips" => $skips
        ]);
    }

    public function show($skipId) {
        $validator = Validator::make([    
            'skip_id' => $skipId
        ],[
            'skip_id' => 'int|min:1|exists:skips,skip_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $skip = Skip::find($skipId);

        return response()->json([
            "skip" => $skip
        ]);
    }

    /**
    * @OA\Post(
    *   path="/skip/add",
    *   summary="Add new skip",
    *   operationId="create",
    *   tags={"skip"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Skip has been installed on site location.",
    *    )
    * )
    */

    public function create(Request $request) {

      
        $errors = [];
        $data = $request->all();
        $skip = new Skip();
        $asset = new \App\Model\AssetInventory();
        $service_category_id = \App\Model\ServiceCategory::where('key', 'like', '%' . 'LUGGER' . '%')->value('service_category_id');

        try{
            $asset_data = [
                "title" => $data['asset_title'],
                "allocated" => 0,
                "serial_number" => $data['asset_title'],
                "asset_number" => $data['asset_title'],
                "service_category_id" => $service_category_id,
                "assignee_id" => isset($data['assignee_id']) && $data['assignee_id'] != null ? $data['assignee_id'] : null,
                "status" => 1
            ];
            $asset_id = $asset->add($asset_data);
    
            $skip_data = [
                "skip_password" => isset($data['skip_password']) && $data['skip_password'] != null ? $data['skip_password'] : null,
                "imei" => isset($data['imei']) && $data['imei'] != null ? $data['imei'] : null,
                "sim_card_number" => isset($data['sim_card_number']) && $data['sim_card_number'] != null ? $data['sim_card_number'] : null,
                "connection_state" => isset($data['connection_state']) && $data['connection_state'] != null ? $data['connection_state'] : null,
                "asset_id" => $asset_id->asset_id, 
                "status" => 1
                ];
           
            $skip->add($skip_data);
        }
        catch(Exception $ex) {
            array_push($errors, [$ex->getMessage()]);
        }
       
        return response()->json([
            "code" => 200,
            "message" => 'New Skip has been created.'
        ]);
    }

    public function change(Request $request, $skipId) {
        $validator = Validator::make([    
            'skip_id' => $skipId
        ],[
            'skip_id' => 'int|min:1|exists:skips,skip_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];
        $data = $request->all();

        if ($request->isMethod('post')) {

            $skip = new Skip();
            $skip = $skip->change($data, $skipId);

            if (!is_object($skip)) {
                $errors = \App\Message\Error::get('skip.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Skip has been updated successfully."
            ]);
        }
    }

    public function remove(Request $request, $skipId) {
        $validator = Validator::make([    
            'skip_id' => $skipId
        ],[
            'skip_id' => 'int|min:1|exists:skips,skip_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $data = $request->all();

        $skip = skip::find($skipId);

        if (!is_object($skip)) {
            return response()->json([
                "code" => 400,
                "message" => "skip not found."
            ]);
        }

        if ($skip->status == 1) {
            $skip->status = 9;
        } else {
            $skip->status = 1;
        }

        $skip->save();
        $skip->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Skip has been deleted.',
        ]);
    }

    public function getSkipAssignmentInfo(Request $request){

        $available_skips = Skip::where('customer_id',null)->with('asset_inventory:asset_id,assigned_to')->get(['skip_id','asset_id','material_id','customer_id'])->toArray();
        foreach($available_skips as &$skip){
            $source_vehicle_id = getAssetTransactionSource('VEHICLE');
            $source_yard_id = getAssetTransactionSource('YARD');
            $source_customer_id = getAssetTransactionSource('CUSTOMER');
            if(isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_vehicle_id){
                $skip['transaction_types'] = ['Receive','Transfer to client'];
            }
            else if(isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_yard_id){
                $skip['transaction_types'] = ['Assign'];
            }
            else if(isset($skip['asset_inventory']['assigned_to']) && $skip['asset_inventory']['assigned_to'] != null && $skip['asset_inventory']['assigned_to'] == $source_customer_id){
                $skip['transaction_types'] = ['Transfer from client'];
            }else{
                $skip['transaction_types'] = [];
            }
            unset($skip['asset_inventory']);
        }
        
        $vehicle_type = getVehicleTypeId('HOOK_TRUCK');
        $hook_trucks = \App\Model\Vehicle::get(['vehicle_id','asset_id','vehicle_code','vehicle_plate_number','vehicle_category_id'])->toArray();
        $yards = \App\Model\Store::where('status',1)->get(['store_id','store_name','latitude','longitude','address'])->toArray();
        $customers = \App\Model\Customer::where('status',1)->get(['customer_id','email','name'])->toArray();
        $customer_sites = \App\Model\Address::where('status',1)->get(['address_id','customer_id','address_title','address','latitude','longitude'])->toArray();
        $skip_levels = \App\Model\SkipLevel::withCount('skips')->orderBy('created_at','DESC')->get()->toArray();
        $transaction_types = \App\Model\TransactionType::orderBy('created_at','DESC')->get(['transaction_type_id','name','key'])->toArray();
        $material_list = \App\Model\Material::orderBy('created_at','DESC')->get(['material_id','parent_id','customer_id','material_code','name','default_unit'])->toArray();

        return response()->json([
            "code" => 200,
            "data" => [
                "available_skips" => $available_skips,
                "hook_trucks" => $hook_trucks,
                "yards" => $yards,
                "customers" => $customers,
                "skip_levels" => $skip_levels,
                "customer_sites" => $customer_sites,
                "transaction_types" => $transaction_types,
                "material_list" => $material_list
            ],
            "message" => 'List Fetched Successfully',
        ]);

    }

    public function skipAssignmentReceival(Request $request){

        $data =  json_decode($request->getContent(),true);
        $validator = Validator::make($request->all(), [
        'skip_id' => 'required|integer|exists:skips,skip_id',
        'key' => 'required|string|in:ASSIGN,RECEIVE,TRANSFER_FROM_CLIENT,TRANSFER_TO_CLIENT',
        'customer_id' => 'nullable|integer|exists:customers,customer_id',
        'address_id' => 'nullable|integer|exists:addresses,address_id',
        'material_id' => 'nullable|integer|exists:material,material_id',
        'aqg_id' => 'nullable|integer|exists:stores,store_id',
        'vehicle_id' => 'nullable|integer|exists:vehicles,vehicle_id',
        ]);
        if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
        }


        if($data['key'] == "ASSIGN"){
            if(isset($data['aqg_id']) && isset($data['aqg_id']) && $data['vehicle_id'] != null && $data['vehicle_id'] != null){
                $asset_id = Skip::where('skip_id',$data['skip_id'])->value('asset_id');
                $transaction_type_id = getAssetTransactionType('ASSIGN');
                $transaction_source_id = getAssetTransactionSource('VEHICLE');
                \App\Model\AssetInventory::where('asset_id',$asset_id)->update(['assigned_to' => $transaction_source_id, 'assignee_id' => $data['vehicle_id']]);
                \App\Model\Vehicle::where('vehicle_id',$data['vehicle_id'])->update(['asset_id' => $asset_id]);
                \App\Model\AssetTransaction::insert([
                    'asset_id' => $asset_id,
                    'transaction_type' => $transaction_type_id,
                    'transfer_from' => $data['aqg_id'],
                    'transfer_to' => $data['vehicle_id'],
                    'order_id' => 1519,
                    "transaction_date" => date('Y-m-d H:i:s'),
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                    "transaction_by" => null

                ]);

            }
        }

        if($data['key'] == "RECEIVE"){
            if(isset($data['aqg_id']) && isset($data['aqg_id']) && $data['vehicle_id'] != null && $data['vehicle_id'] != null){
                $asset_id = Skip::where('skip_id',$data['skip_id'])->value('asset_id');
                $transaction_source_id = getAssetTransactionSource('YARD');
                $transaction_type_id = getAssetTransactionType('RECEIVE');
                \App\Model\AssetInventory::where('asset_id',$asset_id)->update(['assigned_to' => $transaction_source_id, 'assignee_id' => $data['aqg_id']]);
                \App\Model\AssetTransaction::insert([
                    'asset_id' => $asset_id,
                    'transaction_type' => $transaction_type_id,
                    'transfer_from' => $data['vehicle_id'],
                    'transfer_to' => $data['aqg_id'],
                    "transaction_date" => date('Y-m-d H:i:s'),
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                    "transaction_by" => null

                ]);

            }
        }

        if($data['key'] == "TRANSFER_TO_CLIENT"){
            if(isset($data['customer_id']) && isset($data['customer_id']) && $data['vehicle_id'] != null && $data['vehicle_id'] != null){
                $asset_id = Skip::where('skip_id',$data['skip_id'])->value('asset_id');
                $transaction_type_id = getAssetTransactionType('TRANSFER_TO_CLIENT');
                $transaction_source_id = getAssetTransactionSource('CUSTOMER');
                Skip::where('skip_id',$data['skip_id'])
                ->update([
                  'customer_id' => $data['customer_id'] ,
                  'address_id' => $data['address_id'],
                  'material_id' => $data['material_id'],
                ]);
                \App\Model\AssetInventory::where('asset_id',$asset_id)->update(['assigned_to' => $transaction_source_id, 'assignee_id' => $data['customer_id']]);
                \App\Model\AssetTransaction::insert([
                    'asset_id' => $asset_id,
                    'transaction_type' => $transaction_type_id,
                    'transfer_from' => $data['vehicle_id'],
                    'transfer_to' => $data['customer_id'],
                    "transaction_date" => date('Y-m-d H:i:s'),
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                    "transaction_by" => null

                ]);

            }
        }

        if($data['key'] == "TRANSFER_FROM_CLIENT"){
            if(isset($data['customer_id']) && isset($data['customer_id']) && $data['vehicle_id'] != null && $data['vehicle_id'] != null){
                $asset_customer_id = \App\Model\Skip::where('skip_id',$data['skip_id'])->orderBy('created_at','DESC')->value('customer_id');
                if($asset_customer_id == null){
                    return response()->json([
                        "Code" => 403,
                        "is_valid_order" => 0,
                        "message_en" => "Invalid Customer"
                      ]);
                }
                if($asset_customer_id != null){
                    if($asset_customer_id != $data['customer_id']){
                        return response()->json([
                            "Code" => 403,
                            "is_valid_order" => 0,
                            "message_en" => "Invalid Customer"
                          ]);
                    }
                }
                $asset_id = Skip::where('skip_id',$data['skip_id'])->value('asset_id');
                $transaction_type_id = getAssetTransactionType('TRANSFER_FROM_CLIENT');
                $transaction_source_id = getAssetTransactionSource('VEHICLE');
                Skip::where('skip_id',$data['skip_id'])
                ->update([
                  'customer_id' => null ,
                  'address_id' => null,
                ]);
                \App\Model\AssetInventory::where('asset_id',$asset_id)->update(['assigned_to' => $transaction_source_id, 'assignee_id' => $data['vehicle_id']]);
                \App\Model\Vehicle::where('vehicle_id',$data['vehicle_id'])->update(['asset_id' => $asset_id]);
                \App\Model\AssetTransaction::insert([
                    'asset_id' => $asset_id,
                    'transaction_type' => $transaction_type_id,
                    'transfer_from' => $data['customer_id'],
                    'transfer_to' => $data['vehicle_id'],
                    "transaction_date" => date('Y-m-d H:i:s'),
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                    "transaction_by" => null

                ]);

            }
        }


            // if(isset($data['customer_id']) && isset($data['address_id']) && $data['customer_id'] != null && $data['address_id'] != null){
            //     Skip::where('skip_id',$data['skip_id'])
            //     ->update([
            //       'customer_id' => $data['customer_id'] ,
            //       'address_id' => $data['address_id']
            //     ]);
            // }
           
        //     if(isset($data['vehicle_id']) && $data['vehicle_id'] != null){
    
        //         $asset_id = Skip::where('skip_id',$data['skip_id'])->value('asset_id');
        //         \App\Model\Vehicle::where('vehicle_id',$data['vehicle_id'])->update(['asset_id' => $asset_id]);
    
        //     }
    
        // }
        
        return response()->json([
            "code" => 200,
            "data" => "",
            "message" => 'Data updated Successfully',
        ]);

    }
}
