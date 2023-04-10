<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
// use App\Model\Driver as Driver;
use App\Model\User;
use App\Model\Vehicle;
use App\Model\Store;
use Validator;
use DB;
class DriverController extends Controller

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

    public function index(Request $request) {
        $data =  $request->all();
        $driver_group_id = \App\Model\Group::where('group_name', 'DRIVER')->value('group_id');
        $driver_groups = \App\Model\DriverGroup::orderBy('created_at','DESC')->get();
        $vehicles = \App\Model\Vehicle::orderBy('created_at','DESC')->get(['vehicle_id','vehicle_plate_number']);
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        $drivers = User::with(['vehicle'])->where('group_id', $driver_group_id)->orderBy('created_at','DESC');
        if(isset($data['name']) && $data['name'] != ""){
            // $drivers->whereRaw('JSON_EXTRACT(LOWER(first_name), "$.en") LIKE "%'.trim(strtolower($data['name'])).'%"')
            // ->orWhereRaw('JSON_EXTRACT(LOWER(first_name), "$.ar") LIKE "%'.trim(strtolower($data['name'])).'%"')
            // ->orWhereRaw('JSON_EXTRACT(LOWER(last_name), "$.en") LIKE "%'.trim(strtolower($data['name'])).'%"')
            // ->orWhereRaw('JSON_EXTRACT(LOWER(last_name), "$.ar") LIKE "%'.trim(strtolower($data['name'])).'%"')
            // ->orWhereRaw('JSON_EXTRACT(CONCAT(LOWER(`first_name`)," ",LOWER(`last_name`)) , "$.en") LIKE "%'.trim(strtolower($data['name'])).'%"')
            // ->orWhereRaw('JSON_EXTRACT(CONCAT(LOWER(`first_name`)," ",LOWER(`last_name`)) , "$.ar") LIKE "%'.trim(strtolower($data['name'])).'%"');

            $drivers->whereRaw('LOWER(`first_name`) LIKE ? ',['%'.trim(strtolower($data['name'])).'%']);
            $drivers->orWhereRaw('LOWER(`last_name`) LIKE ? ',['%'.trim(strtolower($data['name'])).'%']);
            $drivers->orWhereRaw("CONCAT(LOWER(`first_name`),' ',LOWER(`last_name`)) LIKE ? ",['%'.trim(strtolower($data['name'])).'%']);
        }
        if(isset($data['phone']) && $data['phone'] != ""){
            // $drivers->where('phone', 'like', '%' . $data['phone'] . '%'); // case-sensitive issue
            $drivers->whereRaw('LOWER(`phone`) LIKE ? ',['%'.trim(strtolower($data['phone'])).'%']);
        }
        if(isset($data['email']) && $data['email'] != ""){
            // $drivers->where('email', 'like', '%' . $data['email'] . '%'); // case-sensitive issue
            $drivers->whereRaw('LOWER(`email`) LIKE ? ',['%'.trim(strtolower($data['email'])).'%']);
        }
        if(isset($data['vehicle_id']) && $data['vehicle_id'] != ""){
            $drivers->where('vehicle_id', $data['vehicle_id']);
        }
        if(isset($data['vehicle_plate_number']) && $data['vehicle_plate_number'] != ""){
            $vehicle_plate_number = $data['vehicle_plate_number'];
            $drivers->whereHas('vehicle', function($query) use($vehicle_plate_number){
                $query->whereRaw('LOWER(`vehicle_plate_number`) LIKE ? ',['%'.trim(strtolower($vehicle_plate_number)).'%']);
            });
        }
        if(isset($data['status']) && $data['status'] != ""){
            $drivers->where('status', $data['status']);
        }
        $drivers = $drivers->paginate($data['perPage']);

        return response()->json([
            "data" => $drivers,
            "driver_groups" => $driver_groups,
            "vehicles" => $vehicles
        ]);
    }

    public function createdriver(Request $request, $store_id) 
    {

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|integer|exists:vehicles,vehicle_id'
          ]);
      
          if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
          }
        $errors = [];
        $vehicle_id = null;
        //drivers will be added in users just like other users with group 'DRIVER'
        //extra information of drivers will be saved in drivers table with same user_id

        $data =  json_decode($request->getContent(),true);
        $data = $request->json()->all();//$request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $driver_group_id = \App\Model\Group::where('group_name', 'DRIVER')->value('group_id');
        $data['designation'] = 'DRIVER';
        $data['group_id'] = $driver_group_id;
        $data['role_id'] = 0;
        $data['default_store_id'] = $store_id;
        
        $driver = [];
        $driver_groups = [];
        if (isset($data['driver']) && count($data['driver']) > 0) {
            $driver = $data['driver'];
            unset($data['driver']);
        }

        if (isset($data['driver_groups']) && count($data['driver_groups'])) {
            $driver_groups = $data['driver_groups'];
            unset($data['driver_groups']);
        }
        
        if(isset($data['vehicle_id']) && $data['vehicle_id'] != null){
            $vehicle_id = $data['vehicle_id'];
        }
        unset($data['vehicle_id']);

        $user = new User();
        $user = $user->add($data);
        if($user != null && isset($user->user_id) && $user->user_id != null && $vehicle_id != null){
            Vehicle::where('vehicle_id',$vehicle_id)->update(['driver_id' => $user->user_id]);
        }

        if (!is_object($user)) {
            $errors = \App\Message\Error::get('user.add');

            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        //make entry in drivers table with same user_id

        // $driver['user_id'] = $user->user_id;
        // $driver['employee_id'] = $user->employee_id;
        // $driver['username'] = $user->username;
        // $driver['email'] = $user->email;
        // $driver['reporting_to'] = $user->reporting_to;

        // $vehicle_driver = new \App\Model\Driver();
        // $vehicle_driver = $vehicle_driver->add($driver);

        // if (!is_object($vehicle_driver)) {
        //     $errors = \App\Message\Error::get('driver.add');
            
        //     return response()->json([
        //         "code" => 400,
        //         "errors" => $errors
        //     ]);
        // }

        //step3 add driver in driver groups.
        if (isset($driver_groups)) {
            $drivers_in_drivergroups = new \App\Model\DriversInDriverGroup();
            $drivers_in_drivergroups->addDriverInGroups($driver_groups, $user->user_id);

            $errors = \App\Message\Error::get('driversindrivergroup.add');
            if (isset($add_in_groups_errors) && count($add_in_groups_errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors,
                    "message" => "Driver Added, but some error ocurred while adding driver groups"
                ]);
            }
        }

        // $user = User::with('driver')->find($user->user_id);

        return response()->json([
            "code" => 200,
            "driver" => $user,
            "message" => "Driver Added Successfully",
            "module" => "DRIVER",
            "request_log_id" => $request_log_id
        ]);

        //Fields Validation         
        //     $rules = [
        //         'name' => 'required|string',
        //         'mobile' => 'required|int',
        //         'email' => 'required|string',
        //         'status' => 'required|int',
        //         'rfid' => 'required|int'
        //     ];

        // $validator = Validator::make($data, $rules);
    
        // if ($validator-> fails()) {

        //     return responseValidationError('Fields Validation Failed.', $validator->errors());
        

        // }

        // $insertDriver= Driver::insert($data);

        // if($insertDriver == 1 || $insertDriver == true)
        // {
        //     return response()->json([
        //         "code" => 200,
        //             "message" => "Driver Added Successfully",
        //     ]);
        // }
        // else 
        // {
        //     return response()->json([
        //         "code" => 40,
        //             "message" => "Insertion Not Successful",
        //     ]);
        // }
    }

    public function updateDriver(Request $request, $store_id, $driver_id) 
    {
        $validator = Validator::make([
            'driver_id' => $driver_id
        ],[
            'driver_id' => 'nullable|int|min:1|exists:users,user_id'
        ]);
        $validator = Validator::make([
                    
            $request->all()
        ],[
            'vehicle_id' => 'nullable|integer|exists:vehicles,vehicle_id'
        ]);
       
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $user = User::find($driver_id);
        if (!is_object($user)) {
            return response()->json([
                "code" => 400,
                "message" => "User not found."
            ]);
        }
       

        $data = $request->all();
        if ($data['vehicle_id'] != null) {
           Vehicle::where('driver_id',$driver_id)->update(['driver_id' => null]);
           Vehicle::where('vehicle_id',$data['vehicle_id'])->update(['driver_id' => $driver_id]);
        }else{
           Vehicle::where('driver_id',$driver_id)->update(['driver_id' => null]);
        }
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $driver_group_id = \App\Model\Group::where('group_name', 'DRIVER')->value('group_id');
        $data['designation'] = 'DRIVER';
        $data['group_id'] = $driver_group_id;
        $data['role_id'] = 0;

        $driver = [];
        $driver_groups = [];
        if (isset($data['driver']) && count($data['driver']) > 0) {
            $driver = $data['driver'];
            unset($data['driver']);
        }

        if (isset($data['driver_groups']) && count($data['driver_groups'])) {
            $driver_groups = $data['driver_groups'];
            unset($data['driver_groups']);
        }

        //step1 add entry in `users` table
        $user = new User();
        $user = $user->change($data, $driver_id);
        if (!is_object($user)) {
            $errors = \App\Message\Error::get('user.change');

            return response()->json([
                "code" => 500,
                "errors" => $errors
            ]);
        }

        //step2 extra fields related to drivers will be add  in `drivers` table with same 'user_id'
        // $id = Driver::where('user_id', $user->user_id)->value('id');

        // $driver['user_id'] = $user->user_id;
        // $driver['employee_id'] = $user->employee_id;
        // $driver['username'] = $user->username;
        // $driver['email'] = $user->email;
        // $driver['reporting_to'] = $user->reporting_to;

        // $vehicle_driver = new \App\Model\Driver();

        // if (isset($id) && $id > 0) {
        //     $vehicle_driver = $vehicle_driver->change($driver, $id);

        //     if (!is_object($vehicle_driver)) {
        //         $errors = \App\Message\Error::get('driver.change');
                
        //         return response()->json([
        //             "code" => 500,
        //             "errors" => $errors
        //         ]);
        //     }
        // } else {
        //     $vehicle_driver = $vehicle_driver->add($driver);

        //     if (!is_object($vehicle_driver)) {
        //         $errors = \App\Message\Error::get('driver.add');
                
        //         return response()->json([
        //             "code" => 500,
        //             "errors" => $errors
        //         ]);
        //     }
        // }
        
        //step3 add driver in driver groups
        if (isset($driver_groups)) {
            $drivers_in_drivergroups = new \App\Model\DriversInDriverGroup();
            $drivers_in_drivergroups->addDriverInGroups($driver_groups, $driver_id);

            $errors = \App\Message\Error::get('driversindrivergroup.add');
            
            //echo print_r($add_in_groups_errors, true); exit;
            if (isset($add_in_groups_errors) && count($add_in_groups_errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }
        }
        //step4 add vehicle for driver
        if (isset($data['vehicle_id']) && $data['vehicle_id'] != null) {
           $vehicle = \App\Model\Vehicle::where('vehicle_id',$data['vehicle_id'])->first();
           if($vehicle ==null){
            return response()->json([
                "code" => 400,
                "message" => "Vehicle not found"
            ]);
           }
           $vehicle->update(['driver_id' => $driver_id]);

        }

        return response()->json([
            "code" => 200,
            "message" => "Driver Updated Successfully",
            "module" => "DRIVER",
            "request_log_id" => $request_log_id
        ]);

        // $validator = Validator::make([
                
        //     'driver_id' => $driver_id
        // ],[
        //     'driver_id' => 'nullable|int|min:1|exists:drivers,id',
  
        // ]);
 
        // if ($validator-> fails()){

        //     return responseValidationError('Fields Validation Failed.', $validator->errors());
            

        // }

        // $updateDriver = Driver::where('id', $driver_id)->update($data);
        // if($updateDriver == 1 || $updateDriver == true)
        // {
        //     return response()->json([
        //         "code" => 200,
        //             "message" => "Driver Updated Successfully",
        //     ]);
        // }
        // else 
        // {
        //     return response()->json([
        //         "code" => 404,
        //             "message" => "Driver Not Found",
        //     ]);
        // }
    }

    public function viewDriver(Request $request, $store_id, $driver_id) 
    {
        $validator = Validator::make([  
            'driver_id' => $driver_id
        ],[
            'driver_id' => 'nullable|int|min:1|exists:drivers,user_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $viewDriver =  User::find($driver_id);//Driver::where('id',$driver_id)->get()->toArray();

        if (!is_object($viewDriver)) {
            return response()->json([
                "code" => 404,
                "message" => "Driver Not Found",
            ]);
        } else {
            return response()->json([
                "code" => 200,
                "data"=>$viewDriver,
                "message" => "Driver data loaded",
            ]);
        }
    }

    public function deleteDriver(Request $request, $store_id, $driver_id) 
    {

        $validator = Validator::make([    
            'user_id' => $driver_id
        ],[
            'user_id' => 'int|min:1|exists:users,user_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $data =  $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);
        
        $user = User::find($driver_id);

        if (!is_object($user)) {
            return response()->json([
                "code" => 404,
                "message" => "Driver Not Found",
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

        // Driver::where('user_id', $driver_id)->update(['status' => 9]);

        // $removeDriver= Driver::where('user_id', $driver_id)->delete();

        return response()->json([
            "code" => 200,
            "data"=>$user,
            "message" => "Driver Removed Successfully",
            "module" => "DRIVER",
            "request_log_id" => $request_log_id
        ]);

        // if ($removeDriver == 1 || $removeDriver == true)
        // {
        //     return response()->json([
        //         "code" => 200,
        //         "data"=>$removeDriver,
        //         "message" => "Driver Removed Successfully",
        //         "module" => "DRIVER",
        //         "request_log_id" => $request_log_id
        //     ]);
        // }
        // else 
        // {
        //     return response()->json([
        //         "code" => 404,
        //         "message" => "Driver Not Found",
        //     ]);
        // }    
    }

    public function addDriverFromSaAP(Request $request) {
        $errors = [];
        $code = 201; $message = "Driver Added in AQG successfully.";

        $data = $request->json()->all();//$request->all();
        $request_log_id = $data['request_log_id'];
      
        unset($data['request_log_id']);

        $created_by = "";
        $user_details = auth()->user();
        if(isset($user_details->user_id)){ $created_by = $user_details->user_id; }
        else if(isset($user_details->customer_id)){ $created_by = $user_details->customer_id; }

        $store_id = "";
        $validator = Validator::make([
            'driver_id' => $data['driver_id'],
            'location' => $data['location'],
            'driver_name' => $data['driver_name'],
        ],[
            'driver_id' => 'required|unique:users,erp_id',
            'location' => "required",
            'driver_name' => "required",
        ],
        [
            'driver_id.unique' => 'Driver already exist in AQG'
        ]);
        if ($validator->fails()) {
            return respondWithError($validator->errors(),$request_log_id,400); 
        }

        $error_code = 404;

        if(isset($data['location']) && !empty($data['location'])){
            $location_erp_id = $data['location'];
            $store_obj = Store::where('erp_id',$location_erp_id)->get();
            if(count($store_obj)==0 && $mode == "create"){
                Error::trigger("vehicle.storeid", ["Yard Id not present with this location"]);
                array_push($errors, \App\Message\Error::get('vehicle.storeid'));
            }
            if (isset($errors) && count($errors) > 0) { return respondWithError($errors,$request_log_id,$error_code); }
            $store_id = $store_obj[0]->store_id;
        }

        $sap_driver_id = $data['driver_id'];
        $driver_fullname = $data['driver_name'];
        $name_parts = explode(" ",$driver_fullname);
        $driver_data = array();
        $driver_data['erp_id'] = $sap_driver_id;
        $driver_data['employee_id'] = (int) $sap_driver_id;
        $driver_data['default_store_id'] = $store_id;
        $driver_data['company_id'] = 1;
        $driver_data['first_name'] = $name_parts[0];   
        array_shift($name_parts);
        $driver_data['last_name'] = implode(" ",$name_parts);
        $driver_data['name'] = $driver_fullname;
        $driver_data['email'] = (isset($data['email']) && !empty($data['email'])?trim($data['email']):$sap_driver_id."@alqaryan.com");
        $driver_data['password'] = (isset($data['password']) && !empty($data['password'])?trim($data['password']):$sap_driver_id);
        $driver_data['phone'] = (isset($data['phone']) && !empty($data['phone'])?trim($data['phone']):"");
        $driver_data['gender'] = (isset($data['gender']) && !empty($data['gender'])?trim($data['gender']):"");
        $driver_data['designation'] = "DRIVER";
        $driver_data['status'] = 1;
        $driver_data['role_id'] = 0;
        $driver_data['group_id'] = 17;
        
        $driver_obj = new User();
        $driver_data['created_by'] = $created_by;
        $driver_data['created_at'] = date("Y-m-d H:i:s");
        $driver_obj = $driver_obj->add($driver_data);

        return respondWithSuccess(null, "DRIVER", $request_log_id, $message, $code);
    }
}



