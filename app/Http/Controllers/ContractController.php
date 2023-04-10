<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Model\Contract;
use Validator;
use DB;
use Illuminate\Validation\Rule;
use Auth;
use Illuminate\Validation\Rules\Exists;

class ContractController extends Controller

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

    public function getContractData(Request $request){
     
        $data = $request->all();
        $validator = Validator::make($request->all(), [
          'contract_id' => 'nullable|integer|exists:contracts,contract_id',
        ]);
        if ($validator->fails()) {
          return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        try {

                //Get Contract Data 
            $contract = Contract::select('contract_id','contract_number','contract_type','total_sale_price as price',
            'balance_due as total_price','contract_status','material_location','no_of_lots','start_date','end_date')
            ->where('contract_id',$data['contract_id'])
            ->get()->toArray();

        }catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [
                    "batch_no" => $data['contract_id'],

                    "processing_results" => ["Error in processing!"],
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in fetching contract data.'
            ];
            return response()->json($response);
        }

      
  
        return response()->json([
            "code" => 200,
            "data" => [
                'contract' => $contract
            ],
            'message' => 'Data fetched Successfully'
        ]);
        
   }

    function create(Request $request){

        $data = $request->all();
        $validator = Validator::make($request->all(), [
          'customer_id' => 'nullable|integer|exists:customers,customer_id',
          'contract_number' => 'required',
          'contract_type' => 'required|integer|exists:contract_type,id',
          'price' => 'nullable|numeric',
        //   'price_vat' => 'nullable|numeric',
          'total_price' => 'nullable|numeric',
          'weight' => 'nullable|numeric',
          'unit' => 'nullable|integer|exists:units,id',
          'customer_id' => 'nullable|integer|exists:customers,customer_id',
          'material_location' => 'nullable|integer|exists:addresses,address_id',
          'no_of_lots' => 'nullable|integer',
          'start_date' => 'nullable|date',
          'end_date' => 'required_with:start_date|nullable|date|after:start_date',
          'contract_status' => 'nullable|integer|exists:contract_status,id',
          'payment_terms' => 'nullable',
          'authorized_by(seller)' => 'nullable',
          'designation(seller)' => 'nullable',
          'authorized_by(buyer)' => 'nullable',
          'designation(buyer)' => 'nullable',
          'custom_duties_applied' => 'nullable|boolean'
        ]);
        if ($validator->fails()) {
          return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $contract_data[] = [

            'contract_number' => $data['contract_number'],
            'contract_type' => $data['contract_type'],
            'total_sale_price' => $data['price'],
            'vat' => isset($data['price_vat']) ? $data['price_vat'] : null,
            'balance_due' => $data['total_price'],
            'material_location' => isset($data['material_location']) ? $data['material_location'] : null,
            'no_of_lots' => isset($data['no_of_lots']) ? $data['no_of_lots'] : null,
            'total_weight' => isset($data['weight']) ? $data['weight'] : null,
            'unit' => isset($data['unit']) ? $data['unit'] : null,
            'customer_id' => isset($data['customer_id']) ? $data['customer_id'] : null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'contract_status' => isset($data['contract_status']) ? $data['contract_status'] : null,
            'payment_terms' => isset($data['payment_terms']) ? $data['payment_terms'] : null,
            'authorized_by(seller)' => isset($data['authorized_by_seller']) ? $data['authorized_by_seller'] : null,
            'designation(seller)' => isset($data['designation_seller']) ? $data['designation_seller'] : null,
            'authorized_by(buyer)' => isset($data['authorized_by_buyer']) ? $data['authorized_by_buyer'] : null,
            'designation(buyer)' => isset($data['designation_buyer']) ? $data['designation_buyer'] : null,
            'custom_duties_applied' => isset($data['custom_duties_applied']) ? $data['custom_duties_applied'] : null,
            'created_at' =>  date('Y-m-d H:i:s'),
            'updated_at' =>  date('Y-m-d H:i:s')

        ];

        try {
            \App\Model\Contract::insert($contract_data);
        }
        catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [
                    "processing_results" => ["Error in processing!"],
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in processing contract data.'
            ];
            return response()->json($response);
        }

        return response()->json([
            "code" => 200,
            "message" => "Contract created successfully"
        ]);
      
    }  

    function contractRawData(Request $request,$pagination=false){

        /**Ayesha: 1-7-2022 Filetring contracts on customer_id */
        $user = auth()->guard('oms')->user();
        $customer_id = ($user->customer_id);  
        try {

            $contracts = \App\Model\Contract::select('contract_id','contract_number','start_date',
            'end_date','contract_type','balance_due as total_price','contract_status')
            ->with('contract_type:id,contract_type_title,key','contract_status:id,contract_status_title,key')
            ->where('customer_id', $customer_id)
            ->orderBy('created_at', 'desc')->get()->toArray();
            $contract_types = getContractTypes();
            $contract_statuses = getContractStatuses();
            $units = getUnits();       

        }
        catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [                
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in fetching raw contract data.'
            ];
            return response()->json($response);
        }
      
        return response()->json([
            "code" => 200,
            "data" => [
                "contracts_list" => $contracts,
                "contract_types" => $contract_types,
                "contract_statuses" => $contract_statuses,
                "units" => $units
          ],
          "message" => __("Data fetched Successfully")
        ]);
        
       
      
    }   

    function listForLots(Request $request){

        try {
            $user = auth()->guard('oms')->user();
            $customer_id = $user->customer_id;

            $contracts = \App\Model\Contract::select('contract_id','contract_number')
            ->where('customer_id',$customer_id)
            ->orderBy('created_at', 'desc')->get()->toArray();
            $material_list = \App\Model\Material::select('material_id','material_code','name','default_unit')
            ->whereStatus(1)->get()->toArray();
            $addresses = \App\Model\Address::select('address_id','customer_id','address','address_title')
            ->where('customer_id',$customer_id)->whereStatus(1)->get()->toArray();
            $address_types = \App\Model\AddressType::select('address_type_id','name')
            ->get()->toArray();

        }
        catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [                
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in fetching raw contract data.'
            ];
            return response()->json($response);
        }
      
        return response()->json([
            "code" => 200,
            "data" => [
                "contracts" => $contracts,
                "materials" => $material_list,
                "addresses" => $addresses,
                "address_types" => $address_types,
          ],
          "message" => __("Data fetched Successfully")
        ]);
        
       
      
    }   


    
}
