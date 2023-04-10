<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\Group;
use App\Model\Role;
use Illuminate\Support\Facades\Hash;
use App\Message\Error;
use App;
use DB;
use Validator;

class UserGroupController extends Controller{

    function index(Request $request) {
        $data =  $request->all();
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;
        $groups = Group::with('url')->orderBy('created_at','DESC');
        
        if(isset($data['title']) && $data['title'] != ""){
            $groups->whereRaw('LOWER(`group_name`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
            $groups->orWhereRaw('LOWER(`group_description`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }

        if(isset($data['status']) && $data['status'] != ""){
            $groups->where('status', $data['status']);
        }
        $groups = $groups->paginate($data['perPage']);
        $roles =\App\Model\Role::where('type', 'fleet')->orderBy('created_at','DESC')->get()->toArray();
        
        return response()->json([
            "code" => 200,
            "data" => $groups,
            "roles" => $roles
        ]);
    }

    function create(Request $request) {

        $errors = [];
       
        if ($request->isMethod('post')) {
    
            $data = $request->all();
            $model = new Group();
            $group = $model->add($data);

            $errors = Error::get("group.add");

            if (isset($errors) && count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "message" => "Group is not created.",
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 201,
                "message" => "Group is created successfully",
                "id" => $group->group_id
            ]);
        }
    }

    function change(Request $request, $groupId){
        $validator = Validator::make([    
            'group_id' => $groupId
        ],[
            'group_id' => 'int|min:1|exists:groups,group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        if ($request->isMethod('post')) {

            $data = $request->all();
            $group = new Group();
            $group = $group->change($data, $groupId);
            $errors = Error::get("group.change");
            if (isset($errors) && count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "message" => "Group is not updated" 
                ]);
            }

            return response()->json([
                "code" => 200,
                "group" => $group,
                "message" => "Group is updated"
            ]);
        }
    }

    function remove($groupId) {
        $validator = Validator::make([    
            'group_id' => $groupId
        ],[
            'group_id' => 'int|min:1|exists:groups,group_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $group = Group::find($groupId);

        if (!is_object($group)) {
            return response()->json([
                "code" => 400,
                "message" => "Group not found."
            ]);
        }

        if ($group->status == 1) {
            $group->status = 9;
        } else {
            $group->status = 1;
        }

        $group->save();
        $group->delete();

        return response()->json([
            "code" => 200,
            "message" => "Group is deleted successfully."
        ]);
    }
}
