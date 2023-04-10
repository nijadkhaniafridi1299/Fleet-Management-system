<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use Illuminate\Validation\Rule;
use Auth;
use Illuminate\Validation\Rules\Exists;
use App\Model\Material;

class MaterialController extends Controller

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

    function index(){

        $lots = Material::with('customers:customer_id,name')->whereStatus(1)->get([
            'material_id',
            'material_code',
            'name',
            'default_unit',
            'customer_id'
        ]);
        return response()->json([
            "code" => 200,
            "data" => $lots,
            "message" => __("Material list fetched Successfully")
        ]);
        

    }

    function create(Request $request){

        $data = $request->all();
        $validator = Validator::make($request->all(), [
          'material_code' => 'required',
          'name_en' => 'required',
          'name_ar' => 'required',
          'default_unit' => 'nullable|integer|exists:units,id',
          'customer_id' => 'nullable|integer|exists:customers,customer_id'
        ]);
        if ($validator->fails()) {
          return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $name = array("en" => $data['name_en'],"ar" => $data['name_ar']);
        $name = json_encode($name);

        $materialdata = [

            'material_code' => $data['material_code'],
            'name' => $name,
            'default_unit' => $data['default_unit'],
            'status' => 1,
            'customer_id' => isset($data['customer_id']) ? $data['customer_id'] : null,
            'created_at' =>  date('Y-m-d H:i:s'),
            'updated_at' =>  date('Y-m-d H:i:s')

        ];
       
        \App\Model\Material::insert($materialdata);

        return response()->json([
            "code" => 200,
            "data" => "",
            "message" => __("Data Inserted Successfully")
        ]);
    }

    public function remove($material_id)
    {
        $validator = Validator::make([    
            'material_id' => $material_id
        ],[
            'material_id' => 'nullable|int|min:1|exists:material,material_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        try {
            Material::where('material_id',$material_id)->update(['status'=> 9]);
            Material::where('material_id',$material_id)->delete();

            

            return response()->json([
                "code" => 200,
                "message" => "Material Removed Successfully",
                ]);

        } catch(Exception $ex) {
            return response()->json([
                "code" => 500,
                "error" => $ex->getMessage(),
                "message" => "Material is not deleted."
            ]);
        }
    }

    
}
