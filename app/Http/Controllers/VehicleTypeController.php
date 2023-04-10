<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\VehicleType;
use Validator;


class VehicleTypeController extends Controller
{

    /**
     * @operation_name[en]: List
     */
    public function index(Request $request) {
        $data =  $request->all();
        $vehicle_types = VehicleType::orderBy('created_at','DESC');
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        if(isset($data['title']) && $data['title'] != ""){
            // $vehicle_types->where('vehicle_type', 'like', '%' . $data['title'] . '%'); // case-sensitive issue
            $vehicle_types->whereRaw('LOWER(`vehicle_type`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $vehicle_types->where('status', $data['status']);
        }
       
        $vehicle_types = $vehicle_types->paginate($data['perPage']);

        return response()->json([
            "code" => 200,
            "data" => $vehicle_types
        ]);
    }

    public function show($vehicleTypeId) {
        $validator = Validator::make([    
            'vehicle_type_id' => $vehicleTypeId
        ],[
            'vehicle_type_id' => 'int|min:1|exists:vehicle_types,vehicle_type_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $vehicleType = VehicleType::find($vehicleTypeId);

        return response()->json([
            "code" => 200,
            "data" => $vehicleType
        ]);
    }

    /**
     * @operation_name[en]: Add
     */
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'vehicleType' => 'required',
            'vehicleType.*.vehicle_type' => 'required',
            'status' => 'required'
            ]);
        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $errors = [];

        $data = $request->all();
        $data['vehicleType'] = json_decode($data['vehicleType'], true);
        
       
        $vehicleType = new VehicleType();

        if ($request->hasFile('icon')) {
            $icon = $request->file('icon');
            $data['vehicleType']['icon'] = $vehicleType->upload($icon, 'vehicleType');
        }

        $vehicleType = $vehicleType->add($data);

        if (!is_object($vehicleType)) {
            $errors = \App\Message\Error::get('vehicletype.add');
        }

        if (count($errors) == 0) {
            return response()->json([
                "code" => 201,
                "data" => $vehicleType
            ]);
        }

        return response()->json([
            "code" => 500,
            "errors" => $errors,
            "message" => "Vehicle Type is not created."
        ]);
    }

    /**
     * @operation_name[en]: Edit
     */
    public function change(Request $request, $vehicleTypeId) {
        $validator = Validator::make([    
            'vehicle_type_id' => $vehicleTypeId
        ],[
            'vehicle_type_id' => 'int|min:1|exists:vehicle_types,vehicle_type_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $errors = [];

        $data = $request->all();

        $data['vehicleType'] = json_decode($data['vehicleType'], true);

        $vehicleType = new VehicleType();
        if ($request->hasFile('icon')) {
            $icon = $request->file('icon');
            $data['vehicleType']['icon'] = $vehicleType->upload($icon, 'vehicleType');
        }

        $vehicleType = $vehicleType->change($data, $vehicleTypeId);

        if (!is_object($vehicleType)) {
            $errors = \App\Message\Error::get('vehicletype.change');
        }

        if (count($errors) == 0) {
            return response()->json([
                "code" => 200,
                "data" => $vehicleType,
            ]);
        }

        return response()->json([
            "code" => "500",
            "errors" => $errors,
            "message" => "Vehicle type is not updated."
        ]);
    }

    /**
     * @operation_name[en]: Delete
     */
    public function remove($vehicleTypeId)
    {
        $validator = Validator::make([    
            'vehicle_type_id' => $vehicleTypeId
        ],[
            'vehicle_type_id' => 'int|min:1|exists:vehicle_types,vehicle_type_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $vehicleType = VehicleType::find($vehicleTypeId);
        
        if ($vehicleType->status == 1) {
            $vehicleType->status = 9;
        }
        else {
            $vehicleType->status = 1;
        }

        try {
            $vehicleType->save();
            $vehicleType->delete();

            return response()->json([
                "code" => 200
            ]);

        } catch(Exception $ex) {
            return response()->json([
                "code" => 500,
                "error" => $ex->getMessage(),
                "message" => "vehicle type is not deleted."
            ]);
        }
    }
}
