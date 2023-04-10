<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\DriverGroup;
use Validator;

class DriverGroupController extends Controller
{

    /**
     * @operation_name[en]: List
     */
    public function index(Request $request) {

        $data =  $request->all();
        $driverGroups = DriverGroup::orderBy('created_at','DESC');
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        if(isset($data['title']) && $data['title'] != ""){
            // $driverGroups->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);     
            $driverGroups->whereRaw('JSON_EXTRACT(LOWER(title), "$.en") LIKE "%'.trim(strtolower($data['title'])).'%"')
            ->orWhereRaw('JSON_EXTRACT(LOWER(title), "$.ar") LIKE "%'.trim(strtolower($data['title'])).'%"') ; 

        }
        if(isset($data['status']) && $data['status'] != ""){
            $driverGroups->where('status', $data['status']);
        }
        $driverGroups = $driverGroups->paginate($data['perPage']);

        foreach($driverGroups as &$driverGroup) {
            $title = json_decode($driverGroup['title'], true);
            $driverGroup['titleEn'] = isset($title['en']) ? $title['en'] : '';
            $driverGroup['titleAr'] = isset($title['ar']) ? $title['ar'] : '';
        }
        return response()->json([
            "code" => 200,
            "data" => $driverGroups
        ]);
    }

    public function show($driverGroupId) {
        $validator = Validator::make([    
            'driver_group_id' => $driverGroupId
        ],[
            'driver_group_id' => 'nullable|int|min:1|exists:fm_driver_groups,driver_group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $driverGroup = DriverGroup::find($driverGroupId);

        return response()->json([
            "code" => 200,
            "data" => $driverGroup
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

        $driverGroup = new DriverGroup();
        $driverGroup = $driverGroup->add($data);

        if (!is_object($driverGroup)) {
            $errors = \App\Message\Error::get('drivergroup.add');
        }

        if (count($errors) == 0) {
            return response()->json([
                "code" => 201,
                "data" => $driverGroup,
                "module" => "DRIVERGROUP",
                "request_log_id" => $request_log_id
            ]);
        }

        return response()->json([
            "code" => 500,
            "errors" => $errors,
            "message" => "Driver Group is not created."
        ]);
    }

    /**
     * @operation_name[en]: Edit
     */
    public function change(Request $request, $driverGroupId) {
        $validator = Validator::make([    
            'driver_group_id' => $driverGroupId
        ],[
            'driver_group_id' => 'nullable|int|min:1|exists:fm_driver_groups,driver_group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $driverGroup = new DriverGroup();
        $driverGroup = $driverGroup->change($data, $driverGroupId);

        if (!is_object($driverGroup)) {
            $errors = \App\Message\Error::get('drivergroup.change');
        }

        if (count($errors) == 0) {
            return response()->json([
                "code" => 200,
                "data" => $driverGroup,
                "module" => "DRIVERGROUP",
                "request_log_id" => $request_log_id
            ]);
        }

        return response()->json([
            "code" => "500",
            "errors" => $errors,
            "message" => "Driver Group is not updated."
        ]);
    }

    /**
     * @operation_name[en]: Delete
     */
    public function remove(Request $request, $driverGroupId)
    {
        $validator = Validator::make([    
            'driver_group_id' => $driverGroupId
        ],[
            'driver_group_id' => 'nullable|int|min:1|exists:fm_driver_groups,driver_group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $driverGroup = DriverGroup::find($driverGroupId);

        if ($driverGroup->status == 1) {
            $driverGroup->status = 9;
        }
        else {
            $driverGroup->status = 1;
        }

        try {
            $driverGroup->save();
            $driverGroup->delete();

            return response()->json([
                "code" => 200,
                "message" => "Driver Group Removed Successfully",
                "module" => "DRIVERGROUP",
                "request_log_id" => $request_log_id
            ]);

        } catch(Exception $ex) {
            return response()->json([
                "code" => 500,
                "error" => $ex->getMessage(),
                "message" => "Driver Group is not deleted."
            ]);
        }
    }
}
