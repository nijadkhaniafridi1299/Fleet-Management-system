<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use  App\Model\User as User;
use  App\Model\Customer;
use  App\Model\CustomerWarehouse;
use  App\Model\Address;
use  App\Model\CustomerExtra;
use App\Model\Driver;
use Validator;
use DB;
use App\Model\Store as Store;
use App\Model\Vehicle;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
class AuthController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */

    public function login(Request $request)
    {
        //    Fields Validation 
        $rules = [
            'email' => 'required|string',
            'password' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
    

        $rules = [
            'email' => 'required|string',
            'password' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        
        $email = $request->input('email');

        $password = $request->input('password');
        
        
        //Email Validation

        $value = User::where('email',$email)->get()->toArray();
        User::where('email',$email)->update(['fcm_token_for_web' => $request->exists('fcm_token') ? $request->input('fcm_token') : null]);
        // $value = $value->get()->toArray();

        if (count($value) == 0)
        {
            return response()->json([
                "code" => 403,
                "token" => '',
                "message" => __("Please Enter A Valid Email"),
            ]);
        }
        //Token Generation
        $credentials = $request->only('email', 'password');
        $store_id = $value[0]['default_store_id'];
        $user_id = $value[0]['user_id'];
        $mapinfo = Store::where('store_id',$store_id)->pluck('map_info');
        $mapinfo = json_decode($mapinfo[0]);
        $map_key = DB::table('lastmile_options')->where('option_key', 'GMAP_API_KEY')->pluck('option_value')->toArray();
        $map_key = $map_key[0];
        $temp_name = '{"en":"'.$value[0]['first_name'].' '.$value[0]['last_name'].'","ar":"'.$value[0]['first_name'].' '.$value[0]['last_name'].'"}';
        // dd($temp_name);
        //Successfull Login
        if ( $token = JWTAuth::claims(['user_id' => $user_id, 'name' =>json_decode($temp_name) ,'store_id' => $store_id, 'map_info' => $mapinfo])->attempt($credentials)) {
            $user = User::find($value[0]['user_id']);

            $user->last_login = date('Y-m-d H:i:s');
            $user->ip_address = getIp();
            $user->browser = $request->header('User-Agent');
            $user->is_logged_in = true;

            $user->save();
            $response = $this->respondWithToken($token,$map_key,$user);
            $data = $response->getData();
            User::where('user_id',$user_id)->update(['auth_token' => $data->token]);
            return $response;
        }
        //Incorrect Password
        else
        {
            return response()->json([
                "code" => 404,
                "token" => '',
                    "message" => __("Incorrect Password For ".$email),
            ]);
        }

    }


    public function logout($userId) {
        $errors = [];
        try {
            auth('web')->logout();
            User::where('user_id', $userId)->update(['is_logged_in' => false]);
        } catch(Exception $ex) {
            array_push($errors, [$ex->getMessage()]);
        }

        if (count($errors) > 0) {
            return response()->json([
                "code" => 500,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 200,
            "message" => __("User has been logged out.")
        ]);
    }

    public function omslogin(Request $request)
    {
        //    Fields Validation      
        $rules = [
            'email' => 'required|string',
            'password' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $email = $request->input('email');

        $password = $request->input('password');
        
        //Email Validation
        $value = Customer::where('email',$email)->get()->toArray();
        $customers = Customer::where('email',$email)->pluck('customer_id');
        foreach($customers as $id){
            CustomerExtra::updateOrCreate(
                ['customer_id' => $id] , 
                ['fcm_token_for_web' => $request->exists('fcm_token') ? $request->input('fcm_token') : null]
            );
        }


        if (count($value) == 0)
        {
            return response()->json([
                "code" => 403,
                "token" => '',
                "message" => __("Please Enter A Valid Email"),
            ]);
        }
        
        //Token Generation
        $credentials = $request->only('email', 'password');
        $user_id=$value[0]['customer_id'];
        $map_key = DB::table('options')->where('option_key', 'GMAP_API_KEY')->pluck('option_value')->toArray();
        $map_key = $map_key[0];
        $temp_name = '{"en":"'.$value[0]['name'].'","ar":"'.$value[0]['name'].'"}';

        //Successfull Login
        if ( $token = Auth::guard('oms')->attempt($credentials)) {
            $user = Customer::find($value[0]['customer_id']);
            $user->last_login = date('Y-m-d H:i:s');
            $user->is_logged_in = true;
            $user->save();
            // $warehouse_address = Address::where('customer_id',$value[0]['customer_id'])
            //                     ->select('address_id','address_title','address as address_detail','location_id','map_info')
            //                     ->whereStatus(1)->get()->toArray();
            $warehouse_address = [];
            // foreach($warehouse_address as &$warehouse){
            //     $map_info = json_decode($warehouse['map_info']);
            //     $warehouse['longitude'] = $map_info->longitude;
            //     $warehouse['latitude'] = $map_info->latitude;
            //     unset($warehouse['map_info']);
            // }
           
            // $address = Address::whereHas('location', function($query) {
            //   $query->where('status', 1);
            // })
            // ->whereHas('location.parent', function($query) {
            //   $query->where('status', 1);
            // })->select('address_id','address_title','address as address_detail','location_id','longitude','latitude')
            // ->where('customer_id',$value[0]['customer_id'])
            // ->whereStatus(1)
            // ->get()->toArray();
            $address = [];
            $user['addresses'] = isset($address) ? $address : NULL;
            $user['customer_warehouses'] = isset($warehouse_address) ? $warehouse_address : NULL;
            $fcm_token = isset($request['fcm_token']) ? $request->input('fcm_token') : null;

            return $this->respondWithTokenOMS($token, $map_key, $user ,$fcm_token);
        }
        //Incorrect Password
        else
        {
            return response()->json([
                "code" => 404,
                "token" => '',
                    "message" => __("Incorrect Password For ".$email),
            ]);
        }

    }


   
    public function authenticate(Request $request) {

        $rules = [
            'customer_id' => 'required|exists:customers,customer_id'
        ];

        $validator = Validator::make($request->all(), $rules);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $customer_id = $request->input('customer_id');
        $token = $request->input('email');
        
        $user = \App\Model\Customer::where('customer_id', '=', $customer_id)->first();
        CustomerExtra::updateOrCreate(
            ['customer_id' => $customer_id] ,  
            ['fcm_token_for_web' => $request->exists('token') ? $request->input('token') : null]
        );

        if (isset($user)) {
            try {
                // verify the credentials and create a token for the user
                if (! $token = Auth::guard('oms')->fromUser($user)) {
                    return response()->json(['error' => 'invalid_credentials'], 401);
                }
            } catch (JWTException $e) {
                // something went wrong
                return response()->json(['error' => 'could_not_create_token'], 500);
            }
            // if no errors are encountered we can return a JWT
            $token = compact('token');
            $token = $token['token'];
            $map_key = DB::table('options')->where('option_key', 'GMAP_API_KEY')->pluck('option_value')->toArray();
            $map_key = $map_key[0];

            $user->last_login = date('Y-m-d H:i:s');
            $user->is_logged_in = true;
            $user->save();
            $warehouse_address = [];
            // $warehouse_address = Address::where('customer_id',$customer_id)
            //                     ->select('address_id','address_title','address as address_detail','location_id','map_info')
            //                     ->whereStatus(1)->get()->toArray();
            // foreach($warehouse_address as &$warehouse){
            //     $map_info = json_decode($warehouse['map_info']);
            //     $warehouse['longitude'] = $map_info->longitude;
            //     $warehouse['latitude'] = $map_info->latitude;
            //     unset($warehouse['map_info']);
            // }
           
            // $address = Address::whereHas('location', function($query) {
            //   $query->where('status', 1);
            // })
            // ->whereHas('location.parent', function($query) {
            //   $query->where('status', 1);
            // })->select('address_id','address_title','address as address_detail','location_id','longitude','latitude')
            // ->where('customer_id',$customer_id)
            // ->whereStatus(1)
            // ->get()->toArray();
            $address = [];
            $user['addresses'] = isset($address) ? $address : NULL;
            $user['customer_warehouses'] = isset($warehouse_address) ? $warehouse_address : NULL;
            $fcm_token = null;
            
            return $this->respondWithTokenOMS($token, $map_key, $user, $fcm_token);
            
        }
    }

    public function omslogout($customerId) {
        $errors = [];
        try {
            $user = auth()->guard('oms')->user();
            if($user->customer_id != $customerId){
                return response()->json([
                "Code" => 403,
                "Message" => "Unauthorized User."
                ]);
            }
            auth('oms')->logout();
            Customer::where('customer_id', $customerId)->update(['is_logged_in' => false]);
        } catch(Exception $ex) {
            array_push($errors, [$ex->getMessage()]);
        }

        if (count($errors) > 0) {
            return response()->json([
                "code" => 500,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 200,
            "message" => __("Customer has been logged out.")
        ]);
    }

    public function driverlogin(Request $request)
    {
        //    Fields Validation      
        $rules = [
            'email' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $email = $request->input('email');
        $password = $request->input('password');
        $fcm_token_for_driver_app = $request->input('fcm_token');
 
        // User/Email not found
        if (!User::where('email', '=', $email)->count() > 0) {
            
            return response()->json([
                "code" => 403,
                "success" => false,
                "message" => "The entered email address is incorrect",
                "accessToken" => '',
            ]);
         }

        //check if vehicle is assigned
        $value = User::join('vehicles','users.user_id','=','vehicles.driver_id')->where('users.email',$email)->where("vehicles.deleted_at",null)->first();
        if (!isset($value) || $value == null )
        {
            return response()->json([
                "code" => 403,
                "success" => false,
                "message" => "This driver does not have any vehicle assigned to it. Kindly contact your manager.",
                "accessToken" => '',
            ]);
        }
        $value = $value->toArray();

        if (count($value) == 0)
        {
            return response()->json([
                "code" => 403,
                "success" => false,
                "message" => "This driver does not have any vehicle assigned to it. Kindly contact your manager",
                "accessToken" => '',
            ]);
        }
        //Token Generation
        $credentials = $request->only('email', 'password');
        $vehicle_id = Vehicle::select('vehicle_id','vehicle_plate_number')->where('driver_id',$value['user_id'])->first();
        
       
        //0 = not editable, hide delete/qty/unit/add item on drop-off screen), (1 means editable as current apps)
       
      
        //Successfull Login
        $name=$value["first_name"].' '.$value["last_name"];

        if ( $token = JWTAuth::claims(["email" => $email, "id" => $value['user_id'], "name" => $name,  "vehicle_id" => $vehicle_id["vehicle_id"],"vehicle_plate_number" => $vehicle_id["vehicle_plate_number"]])->attempt($credentials)) {
            $user = User::find($value['user_id']);
            $user->last_login = date('Y-m-d H:i:s');
            $user->is_logged_in = true;
            $user->fcm_token_for_driver_app = $fcm_token_for_driver_app;
            $user->save();

            return response()->json([
                "code" => 200,
                "success" => true,
                "accessToken" => $token,
            ]);
        }
        //Incorrect Password
        else
        {
            return response()->json([
                "code" => 404,
                "success" => false,
                "message" => "The entered password is incorrect",
                "accessToken" => '',
            ]);
        }

    }

    public function saplogin(Request $request)
    {
        //    Fields Validation      
        $rules = [
            'email' => 'required|string',
            'password' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $email = $request->input('email');
        $password = $request->input('password');

        //Email Validation
        $value = User::where('email',$email)->get()->toArray();
        
        if (count($value) == 0)
        {
            return response()->json([
                "code" => 403, "success" => false, "accessToken" => '', "message" => 'No User found with these credentials'
            ]);
        }
        $value = $value[0];
        if($value['group_id']!=8){
            return response()->json([
                "code" => 403, "success" => false, "accessToken" => '', "message" => 'Not SAP User'
            ]);
        }

        //Token Generation
        $credentials = $request->only('email', 'password');
       
        //Successfull Login
        $name=$value["first_name"].' '.$value["last_name"];
        
        if ( $token = JWTAuth::claims(["email" => $email, "user_id" => $value['user_id'], "name" => $name])->attempt($credentials)) {
            $user = User::find($value['user_id']);
            $user->last_login = date('Y-m-d H:i:s');
            $user->is_logged_in = true;
            $user->save();

            return response()->json([
                "code" => 200,
                "success" => true,
                "accessToken" => $token,
                // "user_id" => $value['user_id'],
            ]);
        }
        //Incorrect Password
        else
        {
            return response()->json([
                "code" => 404,
                "success" => false,
                "accessToken" => '',
            ]);
        }
    }

    function azureLogin(Request $request) {
		$post = [
			'grant_type' => 'client_credentials',
			'client_secret' => 'GNO8Q~rnfU4lmne3qkB69jWZ7VCV5.bXC3mNebH_',
			'client_id'   => '7dcb8dd0-1fa6-4a0a-a027-09649717ff5f',
			'resource' => 'https://analysis.windows.net/powerbi/api',
		];
		$url="https://login.microsoftonline.com/f3b1630e-f29e-4b28-b234-9f37da311d67/oauth2/token";
		$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

        try {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $agent);
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //     'Origin: https://aqgnow.alqaryan.com', // send token in header request
            // ));
            
            $result=curl_exec($ch);
            
            $result=json_decode($result);

            return response()->json([ 
                "code" => 200,
                "data" => $result,
                "message" => "Azure Login Successful"
            ]);
        
        } catch(Exception $e) {
            return response()->json([ 
                "code" => 400,
                "data" => $e,
                "message" => "Azure Login UnSuccessful"
            ]);
        }
		

		return response()->json($result);
		//echo print_r($result);
    }

    function azureReportData(Request $request) {
        $token = $request->input('access_token');

        try {
            $url = "https://api.powerbi.com/v1.0/myorg/groups/bfa59e53-c2fc-4289-a668-10e9b5b07267/reports/bc88b92e-a0d5-44ca-9c86-2d65c11e2a81";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: '.$token, // send token in header request
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result=curl_exec($ch);
            $result=json_decode($result);

            return response()->json([ 
                "code" => 200,
                "data" => $result,
                "message" => "Report data fetched successfully"
            ]);

        } catch(Exception $ex) {
            return response()->json([ 
                "code" => 400,
                "data" => $e,
                "message" => "Report data fetch unsuccessfull"
            ]);
        }
        

        return response()->json($result);
    }

    function azureEmbedToken(Request $request) {
        $token = $request->input('access_token');


        try {
            $post = [
                "accessLevel" => "View"
            ];

            $url = "https://api.powerbi.com/v1.0/myorg/groups/bfa59e53-c2fc-4289-a668-10e9b5b07267/reports/bc88b92e-a0d5-44ca-9c86-2d65c11e2a81/GenerateToken";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: '.$token, // send token in header request
                'Content-Type: application/json',
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result=curl_exec($ch);
            $result=json_decode($result);

            return response()->json([ 
                "code" => 200,
                "data" => $result,
                "message" => "Report data fetched successfully"
            ]);

        } catch(Exception $ex) {
            return response()->json([ 
                "code" => 400,
                "data" => $e,
                "message" => "Report data fetch unsuccessfull"
            ]);
        }
        

        return response()->json($result);
    }
}
