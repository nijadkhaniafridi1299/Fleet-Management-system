<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Vehicle;
use App\Model\VehicleGroup;
use DB;

class MonitoringController extends Controller

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
    *   path="/monitoring",
    *   summary="Return the list of vehicles with list of latitude and longitude",
    *   tags={"vehicles"},
    *    @OA\Response(
    *      response=200,
    *      description="List of vehicles with list of latitude and longitude",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of vehicles with list of latitude and longitude",
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

        $list_of_drivers = \App\Model\User::select('user_id', DB::raw("CONCAT(users.first_name,' ',users.last_name) AS name"))->whereDesignation('DRIVER')->get(['user_id','name'])->toArray();
        $list_of_vehicles = Vehicle::get(['vehicle_id','vehicle_plate_number'])->toArray();

        $vehicles = Vehicle::with('driver:user_id,first_name,last_name', 'vehicle_type:vehicle_type_id,icon,svg_icon_path', 'vehicleStatus:status_id,title', 'device:device_id,connection_state', 'device.sensors.sensor_type')
            ->where('status',1)
            ->whereHas('device', function($query) {
            })
          
            ->select('vehicle_id', 'vehicle_plate_number','last_updated_at', 'current_latitude', 'current_longitude', 'current_speed',
                'current_angle','current_mobile_latitude' ,'current_mobile_longitude', 'priority' , 'current_altitude', 'engine_hours',
                'odometer_current_reading', 'locked_statellites','destination_latitude', 'destination_longitude', 'pos', 'srv', 'device_id',
                'status_id', 'driver_id','vehicle_type_id')
            ->orderBy('updated_at','DESC');

              if(isset($data['vehicle_plate_number']) && $data['vehicle_plate_number'] != ""){
                $vehicles->whereRaw('LOWER(`vehicle_plate_number`) LIKE ? ',['%'.trim(strtolower($data['vehicle_plate_number'])).'%']);
              }
              if(isset($data['connection_state']) && $data['connection_state'] != ""){
                $connection_state = $data['connection_state'];
                $vehicles->whereHas('device', function($query) use($connection_state){
                  $query->where('connection_state', $connection_state);  
                });
              
              }
              if(isset($data['status']) && $data['status'] != ""){
                $vehicles->where('status',$data['status']);
              }
              if(isset($data['driver_id']) && $data['driver_id'] != ""){
                $vehicles->where('driver_id',$data['driver_id']);
              }
              if(isset($data['current_speed']) && $data['current_speed'] != ""){
                $vehicles->where('current_speed','>=',$data['current_speed']);
              }
              if(isset($data['from']) && $data['from'] != ""){
                $vehicles->whereDate('created_at','>=',$data['from']);
              }
              if(isset($data['to']) && $data['to'] != ""){
                $vehicles->whereDate('created_at','<=',$data['to']);
              }

        $vehicles = $vehicles->paginate($data['perPage'])->toArray();
       
        return [
            "code" => 200,
            "data" => $vehicles,
            "list_of_drivers" => $list_of_drivers,
            "list_of_vehicles" => $list_of_vehicles,
           
        
        ];
    }
    public function groupMonitoring(Request $request){
        $data =  $request->all(); 
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        $list_of_drivers = \App\Model\User::select('user_id', DB::raw("CONCAT(users.first_name,' ',users.last_name) AS name"))->whereDesignation('DRIVER')->get(['user_id','name'])->toArray();
        $list_of_vehicles = Vehicle::get(['vehicle_id','vehicle_plate_number'])->toArray();
        
        $vehicle_groups = VehicleGroup::with('vehicles_in_vehicle_groups:id,vehicle_id,vehicle_group_id',
        'vehicles_in_vehicle_groups.vehicle:vehicle_id,vehicle_plate_number,priority,current_altitude,engine_hours,odometer_current_reading,locked_statellites,current_latitude,current_longitude,current_speed,current_angle,destination_latitude,destination_longitude,pos,srv,device_id,status_id,driver_id,vehicle_type_id',
        'vehicles_in_vehicle_groups.vehicle.driver:user_id,first_name,last_name',
        'vehicles_in_vehicle_groups.vehicle.vehicle_type:vehicle_type_id,icon,svg_icon_path',
        'vehicles_in_vehicle_groups.vehicle.vehicleStatus:status_id,title',
        'vehicles_in_vehicle_groups.vehicle.device:device_id,connection_state', 'vehicles_in_vehicle_groups.vehicle.device.sensors.sensor_type',
        'vehicles_in_vehicle_groups.vehicle.current_trip:delivery_trip_id,trip_status_id,vehicle_id'
        )
        ->select('vehicle_group_id','title','geofence_id','status','created_by');
         if(isset($data['title']) && $data['title'] != ""){
            $vehicle_groups->where('title','LIKE', "%".$data['title']."%");
         }
         if(isset($data['vehicle_plate_number']) && $data['vehicle_plate_number'] != ""){
            $vehicle_plate_number= $data['vehicle_plate_number'];
             $vehicle_groups->whereHas( 'vehicles_in_vehicle_groups.vehicle', function($query) use($vehicle_plate_number){
              $query->whereRaw('LOWER(`vehicle_plate_number`) LIKE ? ',['%'.trim(strtolower($vehicle_plate_number)).'%']); 
            });
          }
          if(isset($data['current_speed']) && $data['current_speed'] != ""){
            $current_speed = $data['current_speed'];
                $vehicle_groups->whereHas('vehicles_in_vehicle_groups.vehicle', function($query) use($current_speed){
                  $query->where('current_speed', '>=', $current_speed);
                });
          }
          if(isset($data['driver_id']) && $data['driver_id'] != ""){
            $diver_id = $data['driver_id'];
                $vehicle_groups->whereHas('vehicles_in_vehicle_groups.vehicle', function($query) use($diver_id){
                  $query->where('driver_id', $diver_id);
                });
          }
          if(isset($data['status']) && $data['status'] != ""){
              $status = $data['status'];
              $vehicle_groups->whereHas('vehicles_in_vehicle_groups.vehicle', function($query) use($status){
                $query->where('status', $status);
              });
          }
          if(isset($data['connection_state']) && $data['connection_state'] != ""){
                $connection_state = $data['connection_state'];
                $vehicle_groups->whereHas('vehicles_in_vehicle_groups.vehicle.device', function($query) use($connection_state){
                  $query->where('connection_state', $connection_state);  
                });
          }
         if(isset($data['from']) && $data['from'] != ""){
             $vehicle_groups->whereDate('created_at','>=',$data['from']);
          }
          if(isset($data['to']) && $data['to'] != ""){
             $vehicle_groups->whereDate('created_at','<=',$data['to']);
          }
          $vehicle_groups = $vehicle_groups->get()->toArray();
       
        foreach($vehicle_groups as &$veh_group){
          $veh_group['vehicles'] = $veh_group['vehicles_in_vehicle_groups'];
          unset($veh_group['vehicles_in_vehicle_groups']);
          for($i= 0; $i<count($veh_group['vehicles']);$i++){
            $veh_group['vehicles'][$i] = $veh_group['vehicles'][$i]['vehicle'];
          } 
        }
        return [
            "list_of_drivers" => $list_of_drivers,
            "list_of_vehicles" => $list_of_vehicles,
            "list_of_vehicle_groups" => $vehicle_groups
        
        ];
    }
}
