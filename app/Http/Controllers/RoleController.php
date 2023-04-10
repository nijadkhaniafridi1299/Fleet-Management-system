<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use App\Model\Role;
use App\Message\Error;
use Validator;

class RoleController extends Controller{

    function index(Request $request) {

        $roles = Role::orderBy('created_at','DESC')->get();

        return ["data" => $roles];
    }

    function show($roleId) {

        $validator = Validator::make([    
            'role_id' => $roleId
        ],[
            'role_id' => 'int|min:1|exists:roles,role_id'
        ]);

         $role = Role::find($roleId);

        return response()->json([
            "role" => $role
        ]);
    }

    function create(Request $request) {
        
        if ($request->isMethod('post')) {
    
            $data = $request->all();

            // $displayInMenu = $request->input('displayInMenu');
            // if ($displayInMenu == "true") {
            //     if (!isset($data['class'])) {
            //         $errors[] = "Please select display menu icon.";
            //     }
            // }

            //if (count($errors) == 0) {
            $model = new Role();
            $role = $model->add($data);
            $errors = Error::get("role.add");
            
            if (!is_object($role)) {
                return response()->json([
                    "code" => 400,
                    "message" => "Role is not created."
                ]);
            }

            return response()->json([
                "code" => 201,
                "id" => $role->role_id,
                "message" => "Role is created successfully."
            ]);
            //}
        }
    }

    function change(Request $request, $roleId){
        $validator = Validator::make([    
            'role_id' => $roleId
        ],[
            'role_id' => 'int|min:1|exists:roles,role_id'
        ]);

        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        $currentRole =  Role::find($roleId)->toArray();

        if ($request->isMethod('post')) {

            $data = $request->all();
            // $displayInMenu = $request->input('displayInMenu');
            // if ($displayInMenu == "true") {
            //     if (!isset($data['class'])) {
            //         $errors[] = "Please select display menu icon.";
            //     }
            // }

            // if (count($errors) == 0) {
            $role = new Role();
            $role = $role->change($data, $roleId);
            $errors = Error::get("role.change");
            if (!is_object($role)) {
                return response()->json([
                    "code" => 400,
                    "message" => "Role is not updated."
                ]);
            }
            // }
        }

        $roles = Role::pluck('url')->toArray();
        $urls = [];
        $icons = Role::select('class')->where('class', '<>', '')->distinct()->get()->toArray();

        $routes = \Route::getRoutes()->get();
        foreach($routes as $route) {
            $route_name = $route->getName();
            if (isset($route_name) && stripos($route_name, 'debug') === FALSE && !in_array($route_name, $roles)) {
                $urls[] = $route_name;
            }
        }

        $urls[] = $currentRole['url'];

        sort($urls);
        return view('admin::user.role.edit', [
            "currentRole" => $currentRole,
            "urls" => $urls,
            "icons" => $icons,
            "errors" => $errors
        ]);
    }

    function remove($role_id) {
        $validator = Validator::make([    
            'role_id' => $roleId
        ],[
            'role_id' => 'int|min:1|exists:roles,role_id'
        ]);

        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $role = Role::find($role_id);

        if (!is_object($role)) {
            return response()->json([
                "code" => 400,
                "message" => "Role not found."
            ]);
        }

        if ($role->status == 1) {
            $role->status = 9;
        } else {
            $role->status =1;
        }

        $role->save();
        $role->delete();

        return response()->json([
            "code" => 200,
            "message" => "Role deleted successfully."
        ]);
    }

    /**
     * routes api will be called to fetch all routes defined in web.php whose role is not created yet in `roles` table in db 
     */
    function routes() {
        
        $urls = $this->getAllRouteNames();
        return response()->json(['routes' => $urls]); 
    }

    function bulk() {
        $allRoutes = $this->getAllRouteNames();

        $role_data = [];
        $roles = [];
        foreach($allRoutes as $url) {
            $isExist = Role::where('url', $url)->first();

            if (!is_object($isExist)) {
                $role_data['url'] = $url;

                $role = new Role();
                $role = $role->add($role_data);

                array_push($roles, $role);
            }
        }

        $errors = Error::get('role.add');
        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => "500",
                "message" => "Bulk insertion for roles failed!",
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => "200",
            "message" => "Bulk insertion successful",
            "roles" => $roles
        ]);
    }

    function getAllRouteNames() {
        $urls = [];
        $routeList = \Route::getRoutes();

        foreach ($routeList as $url => $value) {
            if (strpos($url, 'api/') > -1) {
                if (isset($value['action']) && isset($value['action']['as'])) {
                    $path_name = $value['action']['as'];
                    if (!(stripos($path_name, 'swagger') > -1)) { //search for special keywords
                        array_push($urls, $path_name);
                    }
                }
            }
        }

        return $urls;
    }
}
