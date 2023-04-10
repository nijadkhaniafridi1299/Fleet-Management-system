<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\VehicleGroup;
use App\Model\GeoFence;
use Validator;

class VehicleGroupController extends Controller
{

    /**
     * @operation_name[en]: List
     */
    public function index(Request $request) {

        $data =  $request->all();
        $geofences = GeoFence::orderBy('created_at','DESC')->get()->toArray();
        $vehicleGroups = VehicleGroup::with('geo_fence')->orderBy('created_at','DESC');
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        if(isset($data['title']) && $data['title'] != ""){
            // $vehicleGroups->where('title', 'like', '%' . $data['title'] . '%');
            $vehicleGroups->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['geofence_id']) && $data['geofence_id'] != ""){
            $vehicleGroups->where('geofence_id', $data['geofence_id']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $vehicleGroups->where('status', $data['status']);
        }
        $vehicleGroups = $vehicleGroups->paginate($data['perPage']);

        return response()->json([
            "code" => 200,
            "data" => $vehicleGroups,
            "geo_fences" => $geofences

        ]);
    }

    public function show($vehicleGroupId) {
        $validator = Validator::make([    
            'vehicle_group_id' => $vehicleGroupId
        ],[
            'vehicle_group_id' => 'int|min:1|exists:vehicle_groups,vehicle_group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $vehicleGroup = VehicleGroup::find($vehicleGroupId);

        return response()->json([
            "code" => 200,
            "data" => $vehicleGroup
        ]);
    }

    /**
     * @operation_name[en]: Add
     */
    public function create(Request $request) {
        $errors = [];
        
        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $vehicleGroup = new VehicleGroup();
        $vehicleGroup = $vehicleGroup->add($data);

        if (!is_object($vehicleGroup)) {
            $errors = \App\Message\Error::get('vehiclegroup.add');
        }

        if (count($errors) == 0) {
            return response()->json([
                "code" => 201,
                "data" => $vehicleGroup,
                "module" => "VEHICLEGROUP",
                "request_log_id" => $request_log_id
            ]);
        }

        return response()->json([
            "code" => 500,
            "errors" => $errors,
            "message" => "Vehicle Group is not created."
        ]);
    }

    /**
     * @operation_name[en]: Edit
     */
    public function change(Request $request, $vehicleGroupId) {
        $validator = Validator::make([    
            'vehicle_group_id' => $vehicleGroupId
        ],[
            'vehicle_group_id' => 'int|min:1|exists:vehicle_groups,vehicle_group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $vehicleGroup = new VehicleGroup();
        $vehicleGroup = $vehicleGroup->change($data, $vehicleGroupId);

        if (!is_object($vehicleGroup)) {
            $errors = \App\Message\Error::get('vehiclegroup.change');
        }

        if (count($errors) == 0) {
            return response()->json([
                "code" => 200,
                "data" => $vehicleGroup,
                "module" => "VEHICLEGROUP",
                "request_log_id" => $request_log_id
            ]);
        }

        return response()->json([
            "code" => "500",
            "errors" => $errors,
            "message" => "Vehicle Group is not updated."
        ]);
    }

    /**
     * @operation_name[en]: Delete
     */
    public function remove(Request $request, $vehicleGroupId)
    {
        $validator = Validator::make([    
            'vehicle_group_id' => $vehicleGroupId
        ],[
            'vehicle_group_id' => 'int|min:1|exists:vehicle_groups,vehicle_group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);


        $vehicleGroup = VehicleGroup::find($vehicleGroupId);

        if ($vehicleGroup->status == 1) {
            $vehicleGroup->status = 9;
        }
        else {
            $vehicleGroup->status = 1;
        }

        try {
            $vehicleGroup->save();
            $vehicleGroup->delete();

            return response()->json([
                "code" => 200,
                "message" => "Vehicle Group is deleted.",
                "module" => "VEHICLEGROUP",
                "request_log_id" => $request_log_id
            ]);

        } catch(Exception $ex) {
            return response()->json([
                "code" => 500,
                "error" => $ex->getMessage(),
                "message" => "Vehicle Group is not deleted."
            ]);
        }
    }
}
