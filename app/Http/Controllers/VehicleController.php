<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Vehicle;
use App\Model\VehicleType;
use App\Model\User;
use App\Model\DeliveryTrip;
use App\Model\Address;
use App\Model\CustomerApprovedVehicle;
use App\Model\Store;
use App\Model\Order;
use App\Model\SapApi;
use Tymon\JWTAuth\Claims\Custom;
use Validator;
use App\Message\Error;


class VehicleController extends Controller

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
    *   path="/vehicles",
    *   summary="Return the list of vehicles",
    *   tags={"Vehicles"},
    *    @OA\Response(
    *      response=200,
    *      description="List of vehicles",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of vehicles",
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
        
        $vehicles = Vehicle::with('driver', 'vehicleStatus', 'vehicle_type')->orderBy('created_at','DESC');

        if(isset($data['vehicle_plate_number']) && $data['vehicle_plate_number'] != ""){
            $vehicles->whereRaw('LOWER(`vehicle_plate_number`) LIKE ? ',['%'.trim(strtolower($data['vehicle_plate_number'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $vehicles->where('status', $data['status']);
        }
        if(isset($data['driver_id']) && $data['driver_id'] != ""){
            $driver_id = $data['driver_id'];
            $vehicles->whereHas('driver', function($query) use($driver_id){
              $query->where('user_id', $driver_id);  
            });
          
        }
        

        if(isset($data['name']) && $data['name'] != ""){
            $driver_name = $data['name'];
            $vehicles->whereHas('driver', function($query) use($driver_name){
                $query->whereRaw('LOWER(`first_name`) LIKE ? ',['%'.trim(strtolower($driver_name)).'%']);
                $query->orWhereRaw('LOWER(`last_name`) LIKE ? ',['%'.trim(strtolower($driver_name)).'%']);
                $query->orWhereRaw("CONCAT(LOWER(`first_name`),' ',LOWER(`last_name`)) LIKE ? ",['%'.trim(strtolower($driver_name)).'%']);
            });
        }

        if(isset($data['pagination']) && $data['pagination']=="false"){
            $vehicles = $vehicles->where("status",1)->get()->toArray();
        }
        else{ $vehicles = $vehicles->paginate($data['perPage']); }

        $services = \App\Model\Service::orderBy('created_at','DESC')->get();
        $vehicle_groups = \App\Model\VehicleGroup::orderBy('created_at','DESC')->get();
        $vehicle_types = \App\Model\VehicleType::orderBy('created_at','DESC')->get();
        $devices = \App\Model\Device::orderBy('created_at','DESC')->get();
        $sensors = \App\Model\Sensor::orderBy('created_at','DESC')->get();
        $commands = \App\Model\Command::orderBy('created_at','DESC')->get();
        $command_types = \App\Model\CommandType::orderBy('created_at','DESC')->get();
        $driver_behaviors = \App\Model\DriverBehavior::orderBy('created_at','DESC')->get();
        $device_protocols = \App\Model\DeviceProtocol::orderBy('created_at','DESC')->get();
        $trailers = \App\Model\Trailer::orderBy('created_at','DESC')->get();

        $driver_group_id = \App\Model\Group::where('group_name', 'DRIVER')->value('group_id');
        $drivers = \App\Model\User::where('group_id', $driver_group_id)->orderBy('created_at','DESC')->get()->toArray();

        $statuses = \App\Model\Status::orderBy('created_at','DESC')->get();
        $parameters = \App\Model\Parameter::orderBy('created_at','DESC')->get();
        $sensor_types = \App\Model\SensorType::orderBy('created_at','DESC')->get();


        return response()->json([
            "data" => $vehicles,
            "vehicle_groups" => $vehicle_groups,
            "vehicle_types" => $vehicle_types,
            "devices" => $devices,
            "services" => $services,
            "sensors" => $sensors,
            "commands" => $commands,
            "command_types" => $command_types,
            "driver_behaviors" => $driver_behaviors,
            "device_protocols" => $device_protocols,
            "trailers" => $trailers,
            "drivers" => $drivers,
            "statuses" => $statuses,
            "parameters" => $parameters,
            "sensor_types" => $sensor_types
        ]);
    }

    public function rawData() {
        $vehicle_groups = \App\Model\VehicleGroup::all();
        $vehicle_types = \App\Model\VehicleType::all();
        $command_types = \App\Model\CommandType::all();
        $device_protocols = \App\Model\DeviceProtocol::all();
        $trailers = \App\Model\Trailer::all();

        $driver_group_id = \App\Model\Group::where('group_name', 'DRIVER')->value('group_id');
        $drivers = \App\Model\User::where('group_id', $driver_group_id)->get()->toArray();

        $statuses = \App\Model\Status::all();
        $parameters = \App\Model\Parameter::all();
        $sensor_types = \App\Model\SensorType::all();


        return response()->json([
            "code" => 200,
            "vehicle_groups" => $vehicle_groups,
            "vehicle_types" => $vehicle_types,
            "command_types" => $command_types,
            "device_protocols" => $device_protocols,
            "trailers" => $trailers,
            "drivers" => $drivers,
            "statuses" => $statuses,
            "parameters" => $parameters,
            "sensor_types" => $sensor_types
        ]);
    }

    public function show($vehicleId) {
        $validator = Validator::make([    
            'vehicle_id' => $vehicleId
        ],[
            'vehicle_id' => 'int|min:1|exists:vehicles,vehicle_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $vehicle = Vehicle::with('driver', 'vehicleStatus', 'vehicle_groups', 'services.service_types', 'device.sensors.sensor_type', 'device.sensors.parameter', 'commands.command_type', 'driver_behaviors.sensor')->find($vehicleId);
        $vehicle_groups = \App\Model\VehicleGroup::all();
        $vehicle_types = \App\Model\VehicleType::all();
        $command_types = \App\Model\CommandType::all();
        $driver_behaviors = \App\Model\DriverBehavior::all();
        $device_protocols = \App\Model\DeviceProtocol::all();
        $trailers = \App\Model\Trailer::all();

        $driver_group_id = \App\Model\Group::where('group_name', 'DRIVER')->value('group_id');
        $drivers = \App\Model\User::where('group_id', $driver_group_id)->get()->toArray();

        $statuses = \App\Model\Status::all();
        $parameters = \App\Model\Parameter::all();
        $sensor_types = \App\Model\SensorType::all();

        return response()->json([
            "vehicle" => $vehicle,
            "vehicle_groups" => $vehicle_groups,
            "vehicle_types" => $vehicle_types,
            "command_types" => $command_types,
            "device_protocols" => $device_protocols,
            "trailers" => $trailers,
            "drivers" => $drivers,
            "statuses" => $statuses,
            "parameters" => $parameters,
            "sensor_types" => $sensor_types
        ]);
    }

    /**
    * @OA\Post(
    *   path="/vehicle/add",
    *   summary="Add new vehicle",
    *   operationId="create",
    *   tags={"Vehicles"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Vehicle is inserted in database",
    *    )
    * )
    */

    public function create(Request $request) {

        $errors = [];
        $data = $request->json()->all();//$request->all();
        $request_log_id = $data['request_log_id'];
      
        unset($data['request_log_id']);

        $vehicle = new Vehicle();

        //step1: create a new vehicle in vehicles
        $vehicle = $vehicle->add($data['vehicle']);

        $errors = \App\Message\Error::get('vehicle.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        //step2: after creating vehicle, add new device in devices that is being installed in that vehicle
        //then add device_id in vehicles table.
        //after successfull device creation , add sensors to it.

        if(isset($data['device']['device_type']) && $data['device']['device_type'] != null && $data['device']['device_type'] != ''){

            $device = new \App\Model\Device();
            $device = $device->add($data['device']);
            if (!is_object($device)) {
                $errors = \App\Message\Error::get('device.add');
                if (isset($errors) && count($errors) > 0) {
                    return response()->json([
                        "code" => 400,
                        "errors" => $errors
                    ]);
                }
            }
            Vehicle::where('vehicle_id', $vehicle['vehicle_id'])->update(['device_id' => $device['device_id']]);

             //add sensors to this device.
             if (isset($data['sensors'])) {
                $sensor = new \App\Model\Sensor();
                $sensor->addSensorsForDevice($data['sensors'], $device['device_id']);

                $errors = \App\Message\Error::get('sensor.add');
                if (isset($errors) && count($errors) > 0) {
                    return response()->json([
                        "code" => 400,
                        "errors" => $errors
                    ]);
                }
            }

        }

            
           
        
        
        //step3: add vehicle in vehicle groups.
        if (isset($data['vehicle_groups'])) {
            $vehicles_in_group = new \App\Model\VehiclesInVehicleGroup();
            $vehicles_in_group->addVehicleInGroups($data['vehicle_groups'], $vehicle['vehicle_id']);
        }
    
        // //step4: add services for vehicle
        // // $data['services'] will be list of service ids which are supposed to be added for vehicle
        if (isset($data['services'])) {
            try {
                \App\Model\Service::whereIn('service_id', $data['services'])->update(['vehicle_id' => $vehicle['vehicle_id']]);
            } catch(\Exception $ex) {
                Error::trigger("vehicle.add", [$ex->getMessage()]);
               
                $errors = \App\Message\Error::get('vehicle.add');
                if (isset($errors) && count($errors) > 0) {
                    return response()->json([
                        "code" => 400,
                        "errors" => $errors
                    ]);
                }
            }
        }

        //step5: add commands in vehicle
        //$data['commands'] list of command ids which will be added in vehicle.
        if (isset($data['commands'])) {
            try {
                \App\Model\Command::whereIn('command_id', $data['commands'])->update(['vehicle_id' => $vehicle['vehicle_id']]);
            } catch(\Exception $ex) {
                Error::trigger("vehicle.add", [$ex->getMessage()]);
               
                $errors = \App\Message\Error::get('vehicle.add');
                if (isset($errors) && count($errors) > 0) {
                    return response()->json([
                        "code" => 400,
                        "errors" => $errors
                    ]);
                }
            }
        }

        return response()->json([
            "code" => 201,
            "vehicle" => $vehicle,
            "message" => 'New Vehicle has been created.',
            "module" => 'VEHICLE',
            "request_log_id" => $request_log_id
        ]);
    }

    public function change(Request $request, $vehicleId) {
        $validator = Validator::make([    
            'vehicle_id' => $vehicleId
        ],[
            'vehicle_id' => 'int|min:1|exists:vehicles,vehicle_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        $data = $request->json()->all();//$request->all();
        $request_log_id = $data['request_log_id'];
      
        unset($data['request_log_id']);

        if ($request->isMethod('post')) {

            //step1: update vehicle data in vehicles table
            $vehicle = new Vehicle();
            $vehicle = $vehicle->change($data['vehicle'], $vehicleId);

            if (!is_object($vehicle)) {
                $errors = \App\Message\Error::get('vehicle.change');

                return response()->json([
                    "code" => 400,
                    "errors" => $errors
                ]);
            }

            //step2: either update or create vehicle device and then update device_id in vehicles table if
            //new device is created.
            //after succesfull device creation/updation add sensors to it.
            $device_id = Vehicle::where('vehicle_id', $vehicleId)->value('device_id');
            if (isset($device_id) && $device_id > 0 ) {
                if (isset($data['device'])) {

                    if (!isset($data['device_password']) || $data['device_password'] == '') {
                        $device = \App\Model\Device::find($device_id);
                        $data['device_password'] = $device->device_password;
                    }

                    $device = new \App\Model\Device();
                    $device = $device->change($data['device'], $device_id);

                    if (!is_object($device)) {
                        array_push($errors, \App\Message\Error::get('device.change'));
                    } else if (isset($data['sensors'])) {
                        //add sensors to this device.
                        $sensor = new \App\Model\Sensor();
                        $sensor->addSensorsForDevice($data['sensors'], $device_id);

                        $sensor_errors = \App\Message\Error::get('sensor.add');
                        if (isset($sensor_errors) && count($sensor_errors) > 0) {
                            array_push($errors, $sensor_errors);
                        }
                    }
                }
            } else {
                if (isset($data['device'])) {
                    $device = new \App\Model\Device();
                    if(isset($data['device']['device_type']) && $data['device']['device_type'] != null && $data['device']['device_type'] != ''){
                    $device = $device->add($data['device']);
                    }
                    if (!is_object($device)) {
                        array_push($errors, \App\Message\Error::get('device.add'));
                    } else {
                        //if device is created successfully then update device_id in vehicles
                        //add sensors to this device.
                        Vehicle::where('vehicle_id', $vehicleId)->update(['device_id' => $device['device_id']]);
                        $sensor = new \App\Model\Sensor();
                        $sensor->addSensorsForDevice($data['sensors'], $device['device_id']);

                        $sensor_errors = \App\Message\Error::get('sensor.add');
                        if (isset($sensor_errors) && count($sensor_errors) > 0) {
                            array_push($errors, $sensor_errors);
                        }
                    }
                }  
            }

            //step3 add vehicle in vehicle groups.
            if (isset($data['vehicle_groups'])) {
                $vehicles_in_vehiclegroups = new \App\Model\VehiclesInVehicleGroup();
                $vehicles_in_vehiclegroups->addVehicleInGroups($data['vehicle_groups'], $vehicleId);
            }

            //step4: add any new services for vehicle
            //$data['services'] will be list of service ids which are supposed to be added for vehicle.
            if (isset($data['services'])) {
                $service = new \App\Model\Service();
                $service->addServicesForVehicle($data['services'], $vehicleId);
            }

            $service_errors = \App\Message\Error::get('service.add');
            if (isset($service_errors) && count($service_errors) > 0) {
                array_push($errors, $service_errors);
            }

            //step5: add commands in vehicle
            //$data['commands'] list of command ids which will be added in vehicle.
            if (isset($data['commands'])) {
                $command = new \App\Model\Command();
                $command->addCommandsForVehicle($data['commands'], $vehicleId);

                $command_errors = \App\Message\Error::get('command.add');
                if (isset($command_errors) && count($command_errors) > 0) {
                    array_push($errors, $command_errors);
                }
            }

            

            //step6: add driver behavior in this vehicle
            //$data['driver_behaviors] is list of driver_behavior_ids
            if (isset($data['driver_behaviors'])) {
                $driver_behavior = new \App\Model\DriverBehavior();
                $driver_behavior->addDriverBehaviorForVehicle($data['driver_behaviors'], $vehicleId);

                $driver_behavior_errors = \App\Message\Error::get('driverbehavior.add');
                if (isset($driver_behavior_errors) && count($driver_behavior_errors) > 0) {
                    array_push($errors, $driver_behavior_errors);
                }
            }

            if (count($errors) > 0) {
                return respondWithError($errors,$request_log_id,500);
            }

            return response()->json([
                "code" => 200,
                "message" => "Vehicle has been updated successfully.",
                "module" =>"VEHICLE",
                "request_log_id" => $request_log_id
            ]);
        }
    }

    public function remove(Request $request, $vehicleId)
    {
        $validator = Validator::make([    
            'vehicle_id' => $vehicleId
        ],[
            'vehicle_id' => 'int|min:1|exists:vehicles,vehicle_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $data = $request->json()->all();//$request->all();
        $request_log_id = $data['request_log_id'];

        $vehicle = Vehicle::find($vehicleId);

        if ($vehicle->status == 1) {
            $vehicle->status = 9;
        }
        else {
            $vehicle->status = 1;
        }

        $vehicle->save();
        $vehicle->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Vehicle has been deleted.',
            "module" =>"VEHICLE",
            "request_log_id" => $request_log_id
        ]);
    }


    public function pickToDropoff(Request $request){

        $data=json_decode($request->getContent(),true);
        $rules = [ 
            'pickup_address_id' => 'required|exists:addresses,address_id',
            'aqg_dropoff_loc_id' => 'nullable|exists:stores,store_id',
            'customer_dropoff_loc_id' => 'nullable|exists:addresses,address_id',
            'vehicle_id' => 'required|exists:vehicles,vehicle_id'
        ];
    

        $validator = Validator::make($data,$rules);
        if ($validator->fails()){

        return responseValidationError('Fields Validation Failed.', $validator->errors());

        }

        $pickup_lat_lng = \App\Model\Address::where('address_id',$data['pickup_address_id'])->get(['latitude','longitude']);
        $pickup_lat = $pickup_lat_lng[0]['latitude'];
        $pickup_lng = $pickup_lat_lng[0]['longitude'];

        if(isset($data['aqg_dropoff_loc_id']) && $data['aqg_dropoff_loc_id'] != null){

            $dropoff_lat_lng = \App\Model\Store::where('store_id',$data['aqg_dropoff_loc_id'])->get(['latitude','longitude']);
            $dropoff_lat = $dropoff_lat_lng[0]['latitude'];
            $dropoff_lng = $dropoff_lat_lng[0]['longitude'];

        }elseif(isset($data['customer_dropoff_loc_id']) && $data['customer_dropoff_loc_id'] != null){

            $dropoff_lat_lng = \App\Model\Address::where('address_id',$data['customer_dropoff_loc_id'])->get(['latitude','longitude']);
            $dropoff_lat = $dropoff_lat_lng[0]['latitude'];
            $dropoff_lng = $dropoff_lat_lng[0]['longitude'];

        }

        $calculate_distance = DeliveryTrip::distance($pickup_lat,$pickup_lng,$dropoff_lat,$dropoff_lng);
        $vehicle_speed = Vehicle::where('vehicle_id',$data['vehicle_id'])->value('speed');
        $vehicleAvgSpeed = ($vehicle_speed == NULL) ? 60 : json_decode($vehicle_speed, true)['avg'];
        $vehicleAvgSpeed = ($vehicleAvgSpeed) / 60; //speed per minute
        $triptime = ($calculate_distance > 0) ? ($calculate_distance / $vehicleAvgSpeed) : 0.00;

        $dropofftime = ($calculate_distance > 0) ? ($calculate_distance/ $vehicleAvgSpeed) : 0.00;

        return response()->json([
            "code" => 200,
               "data" => [
                
                    "dropoff_distance"=> round($calculate_distance,2),
                    "dropoff_time" => round($triptime,2)

               ],
               'message' => 'Distance and Time Fetched Successfully'
           ]); 






    }

    public function getAvailableVehiclesAction(Request $request)
    {
        $data=json_decode($request->getContent(),true);
        $rules = [ 
        'route_date' => 'required|date|date_format:Y-m-d',
        'store_id' => 'required|int|min:1',
        'order_id' => 'required|int|min:1|exists:orders,order_id',
        'shipping_address_id' => 'required|int|min:1',
        ];
    

        $validator = Validator::make($data,$rules);
        if ($validator->fails()){

        return responseValidationError('Fields Validation Failed.', $validator->errors());

        }

$shippingaddressid=$data['shipping_address_id'];
$routeDate=$data['route_date'];
$store_id=$data['store_id'];
$order_id=$data['order_id'];
$trans_source_vehicle = getAssetTransactionSource('VEHICLE');

$customer_id= order::where('order_id',$order_id)->value('customer_id');
$category = checkOrderCatgeory($order_id);
$cancel_trip_status_id = \App\Model\TripStatus::where('key','CANCEL')->value('trip_status_id');

                
        $address=\DB::select("select a.longitude as addresslongitude,a.latitude as addresslatitude
        from addresses a
      where address_id  = $shippingaddressid");
      $address=$address[0];
      $address=(array)$address;
    $latitude=$address['addresslatitude'];
    $longitude=$address['addresslongitude'];
    if($category == "SKIP_COLLECTION"){
        $vehicle =  \DB::select("SELECT v.vehicle_id  , v.vehicle_code , v.vehicle_plate_number , v.driver_id,u.first_name,u.last_name,
        u.name,u.email,v.vehicle_type_id ,vt.vehicle_type ,v.vehicle_category_id , vc.vehicle_category ,vc.key, v.store_id  
        ,current_longitude,current_latitude,current_mobile_latitude,current_mobile_longitude,vt.icon, vt.capacity,
      ( 3959 * acos( cos( radians($latitude) ) * cos( radians( current_latitude ) ) 
      * cos( radians( current_longitude ) - radians($longitude) ) + sin( radians($latitude) ) 
      * sin( radians( current_latitude ) ) ) ) AS distance ,
      (SELECT COUNT(*) 
            FROM inv_assets inv
            WHERE inv.assignee_id = v.vehicle_id AND inv.assigned_to = '$trans_source_vehicle') as `no_of_skips` ,
      (SELECT COUNT(*) 
            FROM delivery_trips dt
            WHERE dt.vehicle_id = v.vehicle_id AND dt.deleted_at IS NULL AND dt.trip_status_id IN (1,2,6)) as `no_of_trips` 
            from vehicles v 
            JOIN vehicle_categories vc on vc.vehicle_category_id = v.vehicle_category_id
            JOIN vehicle_types vt on vt.vehicle_type_id = v.vehicle_type_id 
            JOIN users u on u.user_id = v.driver_id    
            
            where v.status = 1
            AND u.deleted_at is NULL
            AND v.deleted_at is NULL
            ORDER BY distance");  
    }else{
        $vehicle =  \DB::select("SELECT v.vehicle_id  , v.vehicle_code , v.vehicle_plate_number , v.driver_id,u.first_name,u.last_name,
        u.name,u.email,v.vehicle_type_id ,vt.vehicle_type ,v.vehicle_category_id , vc.vehicle_category ,vc.key, v.store_id  
        ,current_longitude,current_latitude,current_mobile_latitude,current_mobile_longitude,vt.icon, vt.capacity,
      ( 3959 * acos( cos( radians($latitude) ) * cos( radians( current_latitude ) ) 
      * cos( radians( current_longitude ) - radians($longitude) ) + sin( radians($latitude) ) 
      * sin( radians( current_latitude ) ) ) ) AS distance ,
      (SELECT COUNT(*) 
            FROM inv_assets inv
            WHERE inv.assignee_id = v.vehicle_id AND inv.assigned_to = '$trans_source_vehicle') as `no_of_skips` ,
      (SELECT COUNT(*) 
            FROM delivery_trips dt
            WHERE dt.vehicle_id = v.vehicle_id AND dt.deleted_at IS NULL AND dt.trip_status_id IN (1,2,6)) as `no_of_trips` 
            from vehicles v 
            JOIN vehicle_categories vc on vc.vehicle_category_id = v.vehicle_category_id
            JOIN vehicle_types vt on vt.vehicle_type_id = v.vehicle_type_id 
            JOIN users u on u.user_id = v.driver_id    
            
            AND v.status = 1
            AND u.deleted_at is NULL
            AND v.deleted_at is NULL
            ORDER BY distance");
    }
        

         $approved_vehicles=CustomerApprovedVehicle::where('customer_id',$customer_id)->where('approved',1)->get()
         ->toArray();
   
          
            
           
    $vehicleArray = array();

    //GetPickupLatLong
    $getPickup=Address::where('address_id',$shippingaddressid)->get(['latitude','longitude'])->first();
    $picklat=$getPickup['latitude'];
    $picklng=$getPickup['longitude'];

    
    //GetDropoffLatLong
    $getDropID=Order::where('order_id',$order_id)->get(['aqg_dropoff_loc_id','customer_dropoff_loc_id'])->first();

    if($getDropID['customer_dropoff_loc_id']!=null)
    {

        $getDropoff=Address::where('address_id',$getDropID['customer_dropoff_loc_id'])->get(['latitude','longitude'])->first();
        $droplat=$getDropoff['latitude'];
        $droplng=$getDropoff['longitude'];
    }
    elseif($getDropID['aqg_dropoff_loc_id']!=null)
    {
        
        $getDropoff=Store::where('store_id',$getDropID['aqg_dropoff_loc_id'])->get(['latitude','longitude'])->first();
        $droplat=$getDropoff['latitude'];
        $droplng=$getDropoff['longitude'];

    }

    if(!isset($droplat) && !isset($droplng))
    {
        $calDistance = 0;    
    }else{
        $calDistance = DeliveryTrip::distance($picklat,$picklng,$droplat,$droplng);
    }  


    if(count($vehicle)>0){
        $vehicle = json_decode( json_encode($vehicle),true);
      
       $temp_name ="";
        foreach ($vehicle as $key => $value) {
            $vehicle =  Vehicle::where('vehicle_id', $value['vehicle_id'])->first();
            
             /////////////////////////////////////////////
                $category = checkOrderCatgeory($order_id);
                if($category == "SKIP_COLLECTION"){

                    $trip_information = DeliveryTrip::where('vehicle_id', $value['vehicle_id'])
                                                    ->orderBy('created_at','DESC')
                                                    ->first([
                                                        'trip_status_id',
                                                        'pickup_check_in',
                                                        'dropoff_check_in'
                                                    ]);
                                                   

                    $trip_status_id = isset($trip_information['trip_status_id']) ? $trip_information['trip_status_id'] : null;
                    
                    if($trip_status_id != null){
                        $lat_lng = getLatLngPickAndStores($value['vehicle_id']);
                        $lat_lng = json_encode($lat_lng);
                        $lat_lng = json_decode($lat_lng,true);
                        
                        $drop_lat_lng = isset($lat_lng) && $lat_lng != null && $lat_lng[0] != null ? $lat_lng[0][0] : null;
                        $pickup_lat_lng = isset($lat_lng) && $lat_lng != null  && $lat_lng[1] != null ? $lat_lng[1][0] : null;
                    }
                    
                    $pick_lat = isset($pickup_lat_lng) && $pickup_lat_lng != null && isset($pickup_lat_lng['pickup_latitude']) && $pickup_lat_lng['pickup_latitude'] != null ? $pickup_lat_lng['pickup_latitude'] : null;
                    $pick_lng = isset($pickup_lat_lng) && $pickup_lat_lng != null && isset($pickup_lat_lng['pickup_longitude']) && $pickup_lat_lng['pickup_longitude'] != null ? $pickup_lat_lng['pickup_longitude'] : null;
                    $drop_lat = isset($drop_lat_lng) && $drop_lat_lng != null && isset($drop_lat_lng['latitude']) && $drop_lat_lng['latitude'] != null ? $drop_lat_lng['latitude'] : null;
                    $drop_lng = isset($drop_lat_lng) && $drop_lat_lng != null && isset($drop_lat_lng['longitude']) && $drop_lat_lng['longitude'] != null ? $drop_lat_lng['longitude'] : null;
                   
                    $pickup_check_in = isset($trip_information['pickup_check_in']) ? $trip_information['pickup_check_in'] : null;
                    $dropoff_check_in = isset($trip_information['dropoff_check_in']) ? $trip_information['dropoff_check_in'] : null;
                    $current_latitude = isset($value['current_latitude']) && $value['current_latitude'] != null ? $value['current_latitude'] : $value['current_mobile_latitude'];
                    $current_longitude = isset($value['current_longitude']) && $value['current_longitude'] != null ? $value['current_longitude'] : $value['current_mobile_longitude'];
                                                    
                    //If vehicle is assigned a trip and trip has started
                    if(isset($trip_status_id) && $trip_status_id == 2){

                        if(isset($dropoff_check_in) && $dropoff_check_in != null){

                            $cal_current_to_shippin_dist = $current_latitude != null && $current_longitude != null &&
                                                           $address['addresslatitude'] != null && $address['addresslongitude'] != null ?
                                                           DeliveryTrip::distance($current_latitude,$current_longitude,$address['addresslatitude'],$address['addresslongitude']) : null;//From current location to pickup address (shipping address) of current order
                            $total_distance = $cal_current_to_shippin_dist;

                        }elseif(isset($pickup_check_in) && $pickup_check_in != null){

                            $cal_current_to_drop_dist = $current_latitude != null && $current_longitude != null &&
                                                        $drop_lat != null && $drop_lng != null ?
                                                        DeliveryTrip::distance($current_latitude,$current_longitude,$drop_lat,$drop_lng) : null;//Calculate distance from dropoff location to to pickup address (shipping address) of current order
                            $cal_drop_to_shippin_dist =  $drop_lat != null && $drop_lng != null &&
                                                         $address['addresslatitude'] != null && $address['addresslongitude'] != null ?
                                                         DeliveryTrip::distance($drop_lat,$drop_lng,$address['addresslatitude'],$address['addresslongitude']) : null;//Calculate distance from dropoff location to to pickup address (shipping address) of current order
                            $total_distance = $cal_current_to_drop_dist + $cal_drop_to_shippin_dist;

                        }else{

                            $cal_current_to_pickup_dist = $current_latitude != null && $current_longitude != null &&
                                                          $pick_lat != null && $pick_lng != null ?
                                                          DeliveryTrip::distance($current_latitude,$current_longitude,$pick_lat,$pick_lng) : null;//Calculate distance from dropoff location to to pickup address (shipping address) of current order
                            $cal_pickup_to_drop_dist = $pick_lat != null && $pick_lng != null &&
                                                       $drop_lat != null && $drop_lng != null ?
                                                       DeliveryTrip::distance($pick_lat,$pick_lng,$drop_lat,$drop_lng) : null;//From pickup to dropoff distance
                            $cal_drop_to_shippin_dist = $drop_lat != null && $drop_lng != null &&
                                                        $address['addresslatitude'] != null && $address['addresslongitude'] != null ?
                                                        DeliveryTrip::distance($drop_lat,$drop_lng,$address['addresslatitude'],$address['addresslongitude']) : null;//Calculate distance from dropoff location to to pickup address (shipping address) of current order
                            $total_distance = $cal_current_to_pickup_dist + $cal_pickup_to_drop_dist + $cal_drop_to_shippin_dist;

                        }
                        
                    }elseif( isset($trip_status_id) && $trip_status_id == 1 ){
                        
                        

                        $cal_current_to_pickup_dist = $current_latitude != null && $current_longitude != null &&
                                                        $pick_lat != null && $pick_lng != null ?
                                                        DeliveryTrip::distance($current_latitude,$current_longitude,$pick_lat,$pick_lng) : null;//Calculate distance from dropoff location to to pickup address (shipping address) of current order
                        $cal_pickup_to_drop_dist = $pick_lat != null && $pick_lng != null &&
                                                    $drop_lat != null && $drop_lng != null ?
                                                    DeliveryTrip::distance($pick_lat,$pick_lng,$drop_lat,$drop_lng) : null;//From pickup to dropoff distance
                        $cal_drop_to_shippin_dist = $drop_lat != null && $drop_lng != null &&
                                                    $address['addresslatitude'] != null && $address['addresslongitude'] != null ?
                                                    DeliveryTrip::distance($drop_lat,$drop_lng,$address['addresslatitude'],$address['addresslongitude']) : null;//Calculate distance from dropoff location to to pickup address (shipping address) of current order
                        $total_distance = $cal_current_to_pickup_dist + $cal_pickup_to_drop_dist + $cal_drop_to_shippin_dist;
                        

                    }else{

                        $cal_current_to_shippin_dist = $current_latitude != null && $current_longitude != null &&
                                                        $address['addresslatitude'] != null && $address['addresslongitude'] != null ?
                                                        DeliveryTrip::distance($current_latitude,$current_longitude,$address['addresslatitude'],$address['addresslongitude']) : null;//From current location to pickup address (shipping address) of current order
                        $total_distance = $cal_current_to_shippin_dist;

                    }
                   
                    $value['distance'] = $total_distance;
                }
            


            $vehicleAvgSpeed = ($vehicle->speed == NULL) ? 60 : json_decode($vehicle->speed, true)['avg'];
            $vehicleAvgSpeed = ($vehicleAvgSpeed) / 60; //speed per minute
            $triptime = ($value['distance'] > 0) ? ($value['distance'] / $vehicleAvgSpeed) : 0.00;

            $dropofftime = ($calDistance > 0) ? ($calDistance/ $vehicleAvgSpeed) : 0.00;

      
            if(!isset($value['name'])){
                $temp_name = $value['first_name'].' '.$value['last_name'];
            }
            else{
                $temp_name = $value['name'];
            }
            $vehicleArray[] = [
                'vehicle_id' => $value['vehicle_id'],
                'current_latitude' => $value['current_latitude'],
                'current_longitude' => $value['current_longitude'],
                'number_plate' => $value['vehicle_plate_number'],
                'vehicle_category_name' => json_decode($value['vehicle_category'],true),
                'vehicle_type_name' => json_decode($value['vehicle_type'],true),
                'vehicle_category_key' => $value['key'],
                'vehicle_code' => $value['vehicle_code'],
                'driver_id' => $value['driver_id'],
                'email' => $value['email'],
                'icon' => $value['icon'],
                'driver_name' => $temp_name,
                'distance' =>round($value['distance'],2),
                'time' => round($triptime,2),
                "dropoff_distance"=> round($calDistance,2),
                "dropoff_time" => round($dropofftime,2),
                "capacity" => $value['capacity'],
                "no_of_trips" => $value['no_of_trips'] ,
                "no_of_skips" => $value['no_of_skips'] 
            
            ];
           
        }

        return response()->json([
             "code" => 200,
                "data" => [
                 
                    'vehicles' => $vehicleArray ,
                    'customer_approved_vehicles' => $approved_vehicles

                ],
                'message' => 'Vehicles Loaded!'
            ]); 
        }else{
            return response()->json([
             "code" => 404,
                "data" => [
                    
                    'vehicles' => [] 

                ],
                'message' => 'No Vehicles Loaded!'
            ]); 
        }     
    }


public function getNearestVehicle(Request $request)

{
    $data=json_decode($request->getContent(),true);

    $rules = [ 
        'order_id' => 'required|int|min:1',
        'shipping_address_id' => 'required|int|min:1',
        ];
    

        $validator = Validator::make($data,$rules);
        if ($validator->fails()){

        return responseValidationError('Fields Validation Failed.', $validator->errors());

        }

    $shippingaddressid=$data['shipping_address_id'];

        $address=\DB::select("select a.longitude as addresslongitude,a.latitude as addresslatitude
        from addresses a
      where address_id  = $shippingaddressid");
      $address=$address[0];
      $address=(array)$address;
    $latitude=$address['addresslatitude'];
    $longitude=$address['addresslongitude'];

      $vehicledistance=\DB::select("SELECT vehicle_id,current_longitude,current_latitude,
      ( 3959 * acos( cos( radians($latitude) ) * cos( radians( current_latitude ) ) 
      * cos( radians( current_longitude ) - radians($longitude) ) + sin( radians($latitude) ) 
      * sin( radians( current_latitude ) ) ) ) AS distance 
     FROM vehicles v 
     where v.deleted_at is NULL
     ORDER BY distance");
    
   return response()->json([
    "code" => 200,
       "data" => [
           
           'vehicledistance' => $vehicledistance

       ],
      
   ]); 


}


public function getMultiUnitFlow()

{
  $getVehicles=   $vehicledistance=\DB::select("SELECT vehicle_id,current_longitude,current_latitude,vehicle_plate_number,current_speed
     FROM vehicles v 
     where v.deleted_at is NULL
     and current_latitude is NOT NULL and current_longitude is NOT NULL");

for($i=0;$i<count($getVehicles);$i++)
{
$latitude[$i]=$getVehicles[$i]->current_latitude;

$longitude[$i]=$getVehicles[$i]->current_longitude;

$url="https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=$latitude[$i]&longitude=$longitude[$i]&localityLanguage=en'";
$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
$result=json_decode($result);
if(isset($result->principalSubdivision))
{
    
    for($i=0;$i<count($getVehicles);$i++)
    {
        $getVehicles[$i]->address=$result->principalSubdivision;

      if($getVehicles[$i]->address=='Riyadh Province')
      {
   $riyadh[]=$getVehicles[$i];
      }
      else if ($getVehicles[$i]->address=='Medina Province')
      {
       $medina[]=$getVehicles[$i];
          }
          else if  ($getVehicles[$i]->address=='Mecca Province')
          {
              $mecca[]=$getVehicles[$i];
          }
          else 
          {
              $others[]=$getVehicles[$i];
          }
    }

}

}
    
   return response()->json([
    "code" => 200,
       "data" => [
           
           'Riyadh' => isset($riyadh)?$riyadh:'',
           'Medina' => isset($medina)?$medina:'',
           'Mecca' => isset($mecca)?$mecca:'',
           'others' => isset($others)?$others:'',
       ],
   ]); 
}

    public function equipment(){
        $equipment = Vehicle::whereIn('vehicle_type_id',[59,60])->get()->toArray();
        $assigned_equipment = \DB::table('orders')
                        ->join('order_items', 'orders.order_id', '=', 'order_items.order_id')
                        ->join('vehicles','vehicles.vehicle_id','=','order_items.equipment_id')
                        ->select('vehicles.vehicle_id','vehicle_code')
                        ->whereIn('order_status_id',[2,3,5,13,14,15,16])
                        ->distinct()
                        ->get();
                        
        return response()->json([
            "equipment" => $equipment,
            "assigned_equipment" => $assigned_equipment
            
        ]);
    }

    public function insertionFromSAP(Request $request) {
        $errors = [];
        $code = 201; $message = "Fleet Asset has been added successfully.";

        $data = $request->json()->all();//$request->all();
        $request_log_id = $data['request_log_id'];
      
        unset($data['request_log_id']);

        $created_by = "";
        $user_details = auth()->user();
        if(isset($user_details->user_id)){ $created_by = $user_details->user_id; }
        else if(isset($user_details->customer_id)){ $created_by = $user_details->customer_id; }

        $allowed_mode = ["create","update"]; $mode = "";

        if(isset($data['mode']) && !empty($data['mode'])){ $mode = strtolower($data['mode']); }
        if($mode == ""){
            Error::trigger("vehicle.sapmode", ["No Mode provided in API."]);
            array_push($errors, \App\Message\Error::get('vehicle.sapmode'));
        } else if(!in_array($mode,$allowed_mode)){
            Error::trigger("vehicle.sapmode", ["Create and Update Mode are allowed only"]);
            array_push($errors, \App\Message\Error::get('vehicle.sapmode'));
        }

        if (isset($errors) && count($errors) > 0) { return respondWithError($errors,$request_log_id); }
        
        $store_id = $veh_type_id = "";
        if($mode == "create"){
            $validator = Validator::make([
                'asset_code' => $data['asset_code'],
                'location' => $data['location'],
                'category' => $data['category_id'],
            ],[
                'asset_code' => 'required|unique:vehicles,erp_id',
                'location' => "required",
                'category' => "required",
            ],
            [
                'asset_code.unique' => 'Vehicle with this asset code already exist. Please use update API'
            ]
        );
        }
        else{
            $validator = Validator::make([
                'asset_code' => $data['asset_code'],
            ],[
                'asset_code' => 'required',
            ]);
        }
        if ($validator->fails()) {
            return respondWithError($validator->errors(),$request_log_id,400); 
        }

        $error_code = 404;
        $vehicle_obj = Vehicle::where("erp_id",$data['asset_code'])->get();
        if($mode == "create" && count($vehicle_obj)>0){
            Error::trigger("vehicle.presence", ["Vehicle with this asset code already exist. Please use update API"]);
            array_push($errors, \App\Message\Error::get('vehicle.presence'));
            $error_code = 409;
        }
        else if($mode == "update" && count($vehicle_obj)==0){
            Error::trigger("vehicle.presence", ["Vehicle with this asset code does not exist. Please use add API"]);
            array_push($errors, \App\Message\Error::get('vehicle.presence'));
        }
        if (isset($errors) && count($errors) > 0) { return respondWithError($errors,$request_log_id,$error_code); }

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
        if(isset($data['category_id']) && !empty($data['category_id'])){
            $veh_type_erp_id = $data['category_id'];
            $veh_type_obj = VehicleType::where('erp_id',$veh_type_erp_id)->get();
            if(count($veh_type_obj)==0 && $mode == "create"){
                Error::trigger("vehicle.typeid", ["Vehicle Category Not present with this Id"]);
                array_push($errors, \App\Message\Error::get('vehicle.typeid'));
            }
            if (isset($errors) && count($errors) > 0) { return respondWithError($errors,$request_log_id,$error_code); }
            $veh_type_id = $veh_type_obj[0]->vehicle_type_id;
        }
        $assigned_vehs = array();
        if(isset($data['driver_id']) && !empty($data['driver_id'])){
            $data['driver_id'] = trim($data['driver_id']);
            $driv_obj = User::where("erp_id",$data['driver_id'])->get(['user_id'])->toArray();
            if(count($driv_obj)>0){
                $assigned_vehs = Vehicle::where([["driver_id","=",$driv_obj[0]['user_id']],["erp_id","!=",$data['asset_code']]])->get(['vehicle_id'])->toArray();
                if(count($assigned_vehs)>0){
                    if(!isset($data['force_assign']) || empty($data['force_assign']) || $data['force_assign']==0){
                        Error::trigger("vehicle.driverassigned", ["Driver is assigned with other vehicles. Use force_assign=1 parameter in API to assign driver to this vehicle."]);
                        array_push($errors, \App\Message\Error::get('vehicle.driverassigned'));
                        return respondWithError($errors,$request_log_id,$error_code); 
                    }
                }
            }
        }

        $vehicle_data = array();
        $vehicle_data['erp_id'] = $data['asset_code'];
        if(!empty($store_id)) { $vehicle_data['store_id'] = $store_id; }
        if(!empty($veh_type_id)) { $vehicle_data['vehicle_type_id'] = $veh_type_id; }

        if(isset($data['serial_no']) && !empty($data['serial_no'])){ $vehicle_data['operation_code'] = $data['serial_no']; }
        if(isset($data['plate_no']) && !empty($data['plate_no'])){ $vehicle_data['vehicle_plate_number'] = $data['plate_no']; }
        
        if(isset($data['manufacturer']) && !empty($data['manufacturer'])){ $vehicle_data['model'] = $data['manufacturer']; }
        if(isset($data['model']) && !empty($data['model'])){ $vehicle_data['year'] = $data['model']; }
        // $vehicle_data['category'] = $data['category_name'];
        $vehicle_data['vehicle_category_id'] = 4;  // own vehicle

        if(count($vehicle_obj)>0){
            $vehicle_obj = $vehicle_obj[0];
            $vehicle_data['last_updated_by'] = $created_by;
            $vehicle_data['updated_at'] = date("Y-m-d H:i:s");
            $vehicle_obj = $vehicle_obj->change($vehicle_data,$vehicle_obj->vehicle_id);
            if(!is_object($vehicle_obj)){ array_push($errors, \App\Message\Error::get('vehicle.change')); }
            $code = 204; $message = "Fleet Asset updated successfully.";
        }
        else{
            $vehicle_data['created_by'] = $created_by;
            $vehicle_data['created_at'] = date("Y-m-d H:i:s");
            $vehicle_obj = new Vehicle();
            $vehicle_obj = $vehicle_obj->add($vehicle_data);
            if(!is_object($vehicle_obj)){ array_push($errors, \App\Message\Error::get('vehicle.add')); }
        }
        if (isset($errors) && count($errors) > 0) { return respondWithError($errors,$request_log_id,$error_code); }

        $driver_id = "";
        if(isset($data['driver_id']) && !empty($data['driver_id'])){
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
            $driver_data['email'] = $sap_driver_id."@alqaryan.com";
            $driver_data['password'] = $sap_driver_id;
            $driver_data['designation'] = "DRIVER";
            $driver_data['status'] = 1;
            $driver_data['role_id'] = 0;
            $driver_data['group_id'] = 17;

            $driver_obj = User::where("erp_id",$sap_driver_id)->get();
            if(count($driver_obj)>0){
                // $driver_data['updated_at'] = date("Y-m-d H:i:s");
                $driver_obj = $driver_obj[0];
                // unset($driver_data['password']);
                // $driver_obj = $driver_obj->change($driver_data,$driver_obj->user_id);
            }
            else{
                $driver_obj = new User();
                $driver_data['created_by'] = $created_by;
                $driver_data['created_at'] = date("Y-m-d H:i:s");
                $driver_obj = $driver_obj->add($driver_data);
            }
            if(is_object($driver_obj)){
                if(count($assigned_vehs)>0){
                    foreach($assigned_vehs as $veh_upd){
                        Vehicle::find($veh_upd['vehicle_id'])->update([ "driver_id" => null ]);
                    }
                }
                $vehicle_obj->driver_id = $driver_obj->user_id;
                $vehicle_obj->save();
            }
        }

        return respondWithSuccess(null, "VEHICLE", $request_log_id, $message, $code);
    }

    public function fetchSAPAssets(Request $request){
        $errors = [];
        $code = 201; $message = "Fleet Asset has been added successfully.";
        $data = $request->all();//$request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $headers = [
            'Cookie' => 'SAP_SESSIONID_AGD_550=gwmSzEf2OkiqrsOpRKRT91_gpgksOBHsnhoAUFaNKsA%3d; sap-usercontext=sap-client=550',
            'Authorization' => 'Basic Y3dhdXNlcjppbml0MTIzNDU=',
        ];
        $method = "GET"; $body = array(); $url = 'http://88.85.251.150:8001/sap/opu/odata/sap/ZAQGNOW_ODATA_SRV/ZAQGNOW_ASSETDATASet?$format=json';
        // $url.='&$filter=(AssetCode eq \'Q3-184\')'; // for first record fetching // Q3/495 for sample
        $veh_obj = Vehicle::where("erp_id","!=",null)->orderBy("vehicle_id","DESC")->take(1)->pluck("erp_id");
        if(!empty($veh_obj[0])){ $url.='&$filter=(AssetCode eq \''.$veh_obj[0].'\')'; }
        $response = callExternalAPI($method,$url,$body,$headers);
        $sap_data = [
            'request' => $url,
            'response' => $response,
        ];
        $sap_obj = new SapApi();
        $sap_api = $sap_obj->add($sap_data);
        $response = json_decode($response,true);
        $assets = $response['d']['results'];
        foreach($assets as $asset){
            $veh_type_id = ""; $store_id = 1000;
            if(isset($data['location']) && !empty($data['location'])){
                $location_erp_id = $data['location'];
                $store_obj = Store::where('erp_id',$location_erp_id)->get();
                if(count($store_obj)>0){ $store_id = $store_obj[0]->store_id; }
            }
            if(isset($asset['CategoryId']) && !empty($asset['CategoryId'])){
                $veh_type_erp_id = $asset['CategoryId'];
                $veh_type_obj = VehicleType::where('erp_id',$veh_type_erp_id)->get();
                if(count($veh_type_obj)>0){
                    $veh_type_id = $veh_type_obj[0]->vehicle_type_id;
                }
            }
            $asset['PlateNo'] = trim($asset['PlateNo']);
            $asset['PlateNo'] = str_replace(" ","",$asset['PlateNo']);
            $vehicle_data = array();
            $vehicle_obj = Vehicle::where("erp_id",$asset['AssetCode'])->get();
            $vehicle_data['erp_id'] = $asset['AssetCode'];
            if(!empty($store_id)) { $vehicle_data['store_id'] = $store_id; }
            if(!empty($veh_type_id)) { $vehicle_data['vehicle_type_id'] = $veh_type_id; }

            if(isset($asset['SerialNo']) && !empty($asset['SerialNo'])){ $vehicle_data['operation_code'] = $asset['SerialNo']; }
            if(isset($asset['PlateNo']) && !empty($asset['PlateNo'])){ $vehicle_data['vehicle_plate_number'] = trim($asset['PlateNo']); }
            
            if(isset($asset['Manufacturer']) && !empty($asset['Manufacturer'])){ $vehicle_data['model'] = $asset['Manufacturer']; }
            if(isset($asset['Model']) && !empty($asset['Model'])){ $vehicle_data['year'] = $asset['Model']; }
            $vehicle_data['vehicle_category_id'] = 4;  // own vehicle
            $vehicle_data['created_by'] = 7; // sap user
            $created_date = (!empty($asset['CreatedDate'])?$asset['CreatedDate']:date("d.m.Y"));
            $created_time = (!empty($asset['CreatedTime'])?$asset['CreatedTime']:"00:00:00");
            $created_at = date("Y-m-d H:i:s",strtotime($created_date." ".$created_time));
            $vehicle_data['created_at'] = date("Y-m-d H:i:s");
            if(count($vehicle_obj)==0){
                $vehicle_obj = new Vehicle();
                $vehicle_obj = $vehicle_obj->add($vehicle_data);
            }
            else{ $vehicle_obj = $vehicle_obj[0]; }
            $error_code = 404;
            if(!is_object($vehicle_obj)){ error_log($asset['AssetCode']." - "); error_log(print_r(\App\Message\Error::get('vehicle.add'),1)); continue; }
            // if(!is_object($vehicle_obj)){ array_push($errors, \App\Message\Error::get('vehicle.add')); }
            // if (isset($errors) && count($errors) > 0) { return respondWithError($errors,$request_log_id,$error_code); }
            $driver_id = "";
            if(isset($asset['DriverId']) && !empty($asset['DriverId']) && $asset['DriverId']!="00000000"){
                $sap_driver_id = $asset['DriverId'];
                $driver_fullname = $asset['DriverName'];
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
                $driver_data['email'] = $sap_driver_id."@alqaryan.com";
                $driver_data['password'] = $sap_driver_id;
                $driver_data['designation'] = "DRIVER";
                $driver_data['status'] = 1;
                $driver_data['role_id'] = 0;
                $driver_data['group_id'] = 17;
                $driver_obj = User::where("erp_id",$sap_driver_id)->get();
                if(count($driver_obj)>0){
                    $driver_obj = $driver_obj[0];
                }
                else{
                    $driver_obj = new User();
                    $driver_data['created_by'] = 7;
                    $driver_data['created_at'] = $created_at;
                    $driver_obj = $driver_obj->add($driver_data);
                }
                if(is_object($driver_obj)){
                    $vehicle_obj->driver_id = $driver_obj->user_id;
                    $vehicle_obj->save();
                }
            }
        }
        $sap_api->is_processed = true;
        $sap_api->save();
        return respondWithSuccess(null, "VEHICLE", $request_log_id, $message, $code);
    }
}
