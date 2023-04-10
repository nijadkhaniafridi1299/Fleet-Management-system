<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\User;
use Validator;

class UserController extends Controller

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
    *   path="/users",
    *   summary="Return the list of users",
    *   tags={"Users"},
    *    @OA\Response(
    *      response=200,
    *      description="List of users",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of users",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */

    public function index(Request $request) {
        $data =  $request->all();
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;
        $user= \Auth::user();

        $driver_group_id = \App\Model\Group::where('role_key','DRIVER')->value('group_id');

        $users = User::with('userUnitSettings', 'group')->where('group_id','!=',$driver_group_id)->orderBy('created_at','DESC');

        if(isset($data['group_id']) && $data['group_id'] != ""){
            $group_id = $data['group_id'];
            $users->whereHas('group', function($query) use($group_id){
              $query->where('group_id', $group_id);  
            });
          
        }
        if(isset($data['status']) && $data['status'] != ""){
            $users->where('status', $data['status']);
        }
        if(isset($data['name']) && $data['name'] != ""){
            // $drivers->where('first_name', 'like', '%' . $data['name'] . '%')
            // ->orWhere('last_name', 'like', '%' . $data['name'] . '%');
            $name = $data['name'];
            $users->where(
                function($query) use($name){
                  return $query
                  ->whereRaw('LOWER(`first_name`) LIKE ? ',['%'.trim(strtolower($name)).'%'])
                  ->orWhereRaw('LOWER(`last_name`) LIKE ? ',['%'.trim(strtolower($name)).'%'])
                  ->orWhereRaw("CONCAT(LOWER(`first_name`),' ',LOWER(`last_name`)) LIKE ? ",['%'.trim(strtolower($name)).'%']);
                 });
     
          
        }
        $user_groups = \App\Model\Group::where('role_key','!=','DRIVER')->orderBy('created_at','DESC')->get();
        $roles = \App\Model\Role::orderBy('created_at','DESC')->whereIn('type',['admin','fleet'])->get()->toArray();
        $days = \App\Model\Day::orderBy('created_at','DESC')->get();
        // \DB::enableQueryLog();
        $users = $users->paginate($data['perPage']);
        // dd(\DB::getQueryLog(),$users);
       
        foreach($users as &$user){
            $user['role_id'] = json_decode($user['role_id']);
        }

        return response()->json([
            "data" => $users,
            "user_groups" => $user_groups,
            "roles" => $roles,
            "days" => $days
        ]);
    }

    public function show($userId) {
        $validator = Validator::make([
                    
            'user_id' => $userId
        ],[
            'user_id' => 'int|min:1|exists:users,user_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $user = User::with('userUnitSettings')->find($userId);

        return response()->json([
            "user" => $user
        ]);
    }

    /**
    * @OA\Post(
    *   path="/user/add",
    *   summary="Add new user",
    *   operationId="create",
    *   tags={"Users"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New User is inserted in database",
    *    )
    * )
    */

    public function create(Request $request, $store_id) {
        $errors = [];
        $data = $request->json()->all();//$request->all();

        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);
        $user = new User();

        //step1: create a new user in users
        $data['user']['default_store_id'] = $store_id;
        $user = $user->add($data['user']);

        if (!is_object($user)) {
            $errors = \App\Message\Error::get('user.add');
        } else {
            //if user is created successfully then update users unit settings

            if (isset($data['users_unit_settings']) && count($data['users_unit_settings']) > 0) {
                $unit_setting = new \App\Model\UsersUnitSetting();
                $unit_setting->addSettingsForUser($data['users_unit_settings'], $user->user_id);

                $setting_errors = \App\Message\Error::get('usersunitsetting.add');

                if (isset($setting_errors) && count($setting_errors) > 0) {
                    array_push($errors, $setting_errors);
                }
            }
        }

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        $user = User::with('userUnitSettings')->find($user->user_id);
        return response()->json([
            "code" => 201,
            "user" => $user,
            "message" => 'New User has been created.',
            "module" => 'USER',
            "request_log_id" => $request_log_id
        ]);
    }

    public function change(Request $request, $userId) {
        $validator = Validator::make([
                    
            'user_id' => $userId
        ],[
            'user_id' => 'int|min:1|exists:users,user_id',
  
        ]);

        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();
            $request_log_id = $data['request_log_id'];
            unset($data['request_log_id']);


            //step1: update user data in users table
            $user = new User();

            $user = $user->change($data['user'], $userId);

            if (!is_object($user)) {
                $errors = \App\Message\Error::get('user.change');
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            } 
            //update users unit settings

            if (isset($data['users_unit_settings']) && count($data['users_unit_settings']) > 0) {
                $unit_setting = new \App\Model\UsersUnitSetting();
                
                $unit_setting->addSettingsForUser($data['users_unit_settings'], $user->user_id);

                $setting_errors = \App\Message\Error::get('usersunitsetting.add');

                if (isset($setting_errors) && count($setting_errors) > 0) {
                    array_push($errors, $setting_errors);
                }
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            $user = User::with('userUnitSettings')->find($userId);
            return response()->json([
                "code" => 200,
                "user" => $user,
                "message" => "User has been updated successfully.",
                "module" => "USER",
                "request_log_id" => $request_log_id
            ]);
        }
    }

    public function remove(Request $request, $userId)
    {
        $validator = Validator::make([
                    
            'user_id' => $userId
        ],[
            'user_id' => 'int|min:1|exists:users,user_id',
        ]);

        $data = $request->all();
        $request_log_id = $data['request_log_id'];

        $user = User::find($userId);

        if (!is_object($user)) {
            return response()->json([
                "code" => 400,
                "message" => "User not found."
            ]);
        }

        if ($user->status == 1) {
            $user->status = 9;
        }
        else {
            $user->status = 1;
        }

        $user->save();
        $user->delete();

        return response()->json([
            "code" => 200,
            "message" => "User has been deleted.",
            "module" => "USER",
            "request_log_id" => $request_log_id
        ]);
    }

    public function getActiveSessions() {
        $users = User::where('is_logged_in', true)->orderBy('created_at')->get()->toArray();

        return ["data" => $users];
    }
}
