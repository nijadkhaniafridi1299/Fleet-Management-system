<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class RoleAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->method() == 'POST' || $request->method() == 'Post' || $request->method() == 'post'){

            if (!$request->hasFile("icon") && !$request->hasFile("attachment") && !$request->hasFile("select_file")
             && !$request->has("json_data")) {
                //if request data does not contain binary data then it must contain data in json format.
                $data =  json_decode($request->getContent(),true);
            
                if($data == null || $data == ''){
                return response()->json([
                        "code" => 400,
                        "message" => __("Request Not Supported.Expecting Data in Post Request!"),
                    ]); 
                }
            }
            
        }
     
        $user = Auth::user();

        $user_roles[] = json_decode($user->group_id);
        $roles = DB::table('groups')->whereIn("group_id", $user_roles)->select(\DB::raw("role_key"))->pluck('role_key')->toArray();
           
        $allowedusers = ["Administrator User","ControlTower","Administrator","Site Surveyor","Supervisor"];

        if (!array_intersect($allowedusers, $roles)) {
            
            //if user is not and Administrator then check for specific roles.
            $route_name = $request->route()[1]['as'];
            $role_ids = \App\Model\Group::find($user->group_id)['role_id']; 
            $role_ids = json_decode($role_ids, true);

            //role_ids array is empty it means, all roles have been assigned to this group..
            if (isset($role_ids) && count($role_ids) > 0) {

                if ($user->role_id != '[]' && $user->role_id != '0') {
                    $specific_role_ids = json_decode($user->role_id, true);
                    
                    $role_ids = array_merge($role_ids, $specific_role_ids); 
                }

                $role_urls = \App\Model\Role::whereIn('role_id', $role_ids)->pluck('url')->toArray();
                
                if (!in_array($route_name, $role_urls)) {
                    return response()->json([
                        "code" => 401,
                        "token" => '',
                        "roles" => $role_urls,
                        "message" => " Un-Authorized Access"
                    ]);
                } 
            }
        }
                     
        if (isset($request->route()[2]['store_id'])) {
            $storeid = $request->route()[2]['store_id'];

            if (($storeid != $user->default_store_id)) {
                return response()->json([
                    "code" => 401,
                    "message" => "Un-Authorized Access"
                ]);            
            }
        }
                                 
        return $next($request);
    }
}
