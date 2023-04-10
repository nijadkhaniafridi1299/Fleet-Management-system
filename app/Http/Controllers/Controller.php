<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

/** @OA\Info(title="Fleet Managment API", version="1") */

class Controller extends BaseController
{
    protected function respondWithToken($token,$map_key,$user)
    {
        $group = \App\Model\Group::find($user->group_id);
        $role_id =  $group->role_id;

        $role_ids = [];
        if (isset($role_id)) {
            $role_ids = $role_id;
        }

        if (isset($user['role_id']) && $user['role_id'] != '[]' && $user['role_id'] != '0' && $user['role_id'] != '') {
            $user_specific_role_ids = json_decode($user['role_id'], true);


            if (count($user_specific_role_ids) > 0) {
                array_push($role_ids, $user_specific_role_ids);
            }
        }

        $roles = \App\Model\Role::whereIn('role_id', $role_ids)->get(['action','subject'])->toArray();
        $roles == [] ? $roles = [['action' => 'manage' , 'subject' => 'all']] : null;

        return response()->json([
            'code' => 200,
            "customer_id" => $user['customer_id'],
            "admin_id" => $user['user_id'],
            "name" => $user['first_name'],
            "email" => $user['email'],
            "addresses" => $user['addresses'],
            "group" => $group,
            "roles" => $roles,
            "user_settings" => [
                "show_object_tail" => $user['show_object_tail'],
                "object_tail_color" => $user['object_tail_color'],
                "remember_last_map_position" => $user['remember_last_map_position'],
                "remember_last_map_zoom" => $user['remember_last_map_zoom'],
                "show_zoom_slider_control" => $user['show_zoom_slider_control'],
                "show_select_tile_control" => $user['show_select_tile_control'],
                "default_selected_tile" => $user['default_selected_tile'],
                "show_tiles_in_select_tile" => $user['show_tiles_in_select_tile'],
                "show_layers_control" => $user['show_layers_control'],
                "show_layers_in_layers_control" => $user['show_layers_in_layers_control'],
                "show_utilities_control" => $user['show_utilities_control'],
                "show_utilities_in_utilities_control" => $user['show_utilities_in_utilities_control'],
                "default_map_center_latitude" => $user['default_map_center_latitude'],
                "default_map_center_longitude" => $user['default_map_center_longitude'],
                "default_unit_location_latitude" => $user['default_unit_location_latitude'],
                "default_unit_location_longitude" => $user['default_unit_location_longitude'],
            ],
            'token' => $token,
            'map_key' => $map_key,
            'token_type' => 'bearer',
            "message" => "Login Success",
            'expires_in' => Auth::factory()->getTTL() * 60
        ], 200);
    }

    protected function respondWithTokenOMS($token,$map_key,$user,$fcm_token)
    {
        $group = \App\Model\Group::find($user->group_id);
        $role_id =  $group->role_id;

        $role_ids = [];
        if (isset($role_id)) {
            $role_ids = $role_id;
        }

        if (isset($user['role_id']) && $user['role_id'] != '[]' && $user['role_id'] != '0' && $user['role_id'] != '') {
            $user_specific_role_ids = json_decode($user['role_id'], true);


            if (count($user_specific_role_ids) > 0) {
                array_push($role_ids, $user_specific_role_ids);
            }
        }

        $roles = \App\Model\Role::whereIn('role_id', $role_ids)->get(['action','subject'])->toArray();
        $roles == [] ? $roles = [['action' => 'manage' , 'subject' => 'all']] : null;

        try{
            
            \App\Model\Customer::where('customer_id',$user->customer_id)->update(['fcm_token' => $fcm_token]);
        
        }
        catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [                
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in saving fcm token for customer.'
            ];
            return response()->json($response);
        }


        return response()->json([
            'code' => 200,
            "customer_id" => $user['customer_id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "addresses" => $user['addresses'],
            "group" => $group,
            "roles" => $roles,
            "customer_warehouses" => $user['customer_warehouses'],
            'token' => $token,
            'map_key' => $map_key,
            'token_type' => 'bearer',
            "message" => "Login Success",
            'expires_in' => Auth::factory()->getTTL() * 60
        ], 200);
    }

   public function sensor(Request $request)
    {
      $data['client_id']= 0;
      $origin = $request->headers->all();
      $request = $request->all();
      $server = $_SERVER;

      $data['body'] = json_encode(['request'=>$request,'server'=>$server,'origin'=>$origin]);
      $data['fname'] = 'sensor_data';
      $data['device_type'] = 1;
      $data['api_ver'] = 1;
      $data['record_status'] = 1;
      $data['transaction_id'] = 1;
      $data['mobile_brand'] = 1;

      $model = new \App\Model\MobileRequest();
      $model->add($data);

      echo 'thanks';

    }


}
