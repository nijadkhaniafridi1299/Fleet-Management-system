<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use Illuminate\Validation\Rule;
use Auth;
use Illuminate\Validation\Rules\Exists;
use App\Model\CustomerLot;

class LotController extends Controller

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

    function index(Request $request){

        $user = auth()->guard('oms')->user();
        $customer_id = ($user->customer_id); 
        
        if(isset($request['pagination']) && $request['pagination'] == "true"){
                $lots = CustomerLot::whereStatus(1)->with('address:address_id,address_title,address')
                ->where('customer_id',$customer_id)->select([
                'customer_lot_id',
                'lot_number',
                'address_id',
                'customer_id',
                'price',
                'contract_id'
            ])->paginate(100)->toArray();
        }else{
            
            $lots = CustomerLot::whereStatus(1)->with('address:address_id,address_title,address')
                ->where('customer_id',$customer_id)->get([
                'customer_lot_id',
                'lot_number',
                'address_id',
                'customer_id',
                'price',
                'contract_id'
            ])->toArray();
        }
       
        return response()->json([
            "code" => 200,
            "data" => $lots,
            "message" => __("Lots fetched Successfully")
        ]);
        

    }

    function create(Request $request){

        $data = $request->all();
        $validator = Validator::make($request->all(), [
          'address_id' => 'nullable|integer|exists:addresses,address_id',
          'lot_number' => 'required',
          'price' => 'required|numeric',
          'materials' => 'nullable|exists:material,material_id',
          'units' => 'nullable|exists:units,id',
          'contract_id' => 'nullable|exists:contracts,contract_id'
        ]);
        if ($validator->fails()) {
          return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $lot_material_array = [];
        $user = auth()->guard('oms')->user();
        $data['customer_id'] = ($user->customer_id);  
        $customer_id = $data['customer_id'];
        $customerlotsdata = [

            'lot_number' => $data['lot_number'],
            'address_id' => $data['address_id'],
            'customer_id' => $customer_id,
            'contract_id' => $data['contract_id'],
            'status' => 1,
            'created_at' =>  date('Y-m-d H:i:s'),
            'updated_at' =>  date('Y-m-d H:i:s')

        ];
        
        $customer_lot_id = \App\Model\CustomerLot::insertGetId($customerlotsdata);
        
        foreach($data['materials'] as $material_id){

            $customerlotmaterial[] = [

                'customer_lot_id' => $customer_lot_id,
                'material_id' => $material_id,
                'status' => 1,
                'created_at' =>  date('Y-m-d H:i:s'),
                'updated_at' =>  date('Y-m-d H:i:s')
    
                ];

            array_push($lot_material_array,$customerlotmaterial);

        }
        \App\Model\CustomerLotMaterial::insert($customerlotmaterial);

        return response()->json([
            "code" => 200,
            "data" => "",
            "message" => __("Data Inserted Successfully")
        ]);
    }

    
}
