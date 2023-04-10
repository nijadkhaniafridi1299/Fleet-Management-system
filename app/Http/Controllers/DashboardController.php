<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Device;
use App\Model\Vehicle;
use App\Model\User;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\Location;
use App\Model\Role;
use App\Model\Event;
use App\Model\EventStatus;

class DashboardController extends Controller

{
    public function index() {
        $a_vehicles = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
                               ->where('fm_devices.connection_state',1);
        $active_vehicles = $a_vehicles->count();
        $moving = $a_vehicles->where('vehicles.current_speed','>',0)->count();
        $stationary_engine_on = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
        ->where('fm_devices.connection_state',1)->where('vehicles.current_speed',0)->where('vehicles.current_ignition',1)->count();
        $stationary_engine_off = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
        ->where('fm_devices.connection_state',1)->where('vehicles.current_speed',0)->where('vehicles.current_ignition',0)->count();
        $ia_vehicles = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
                                ->where('connection_state',0);
        $inactive_vehicles = $ia_vehicles->count();
        $total_vehicles = $inactive_vehicles + $active_vehicles;
        $active_units_percentage = ($active_vehicles/$total_vehicles)*100;
        $inactive_units_percentage = ($inactive_vehicles/$total_vehicles)*100; 
      

        return response()->json([
            "active_vehicles" => $active_vehicles,
            "moving" => $moving,
            "stationary engine_on" => $stationary_engine_on, 
            "stationary engine_off" => $stationary_engine_off,
            "off_line_vehicles" => $inactive_vehicles, //27-10-2022
            "percentage of active_units" => $active_units_percentage,
            "percentage of inactive_units" => $inactive_units_percentage,
           
            
        ]);
    }

    public function indexWithData() {
        $a_vehicles = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
                               ->where('fm_devices.connection_state',1);
        $active_vehicles = $a_vehicles->get();
        $moving = $a_vehicles->where('vehicles.current_movement',1)->get();
        $stationary_engine_on = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
        ->where('fm_devices.connection_state',1)->where('vehicles.current_movement',0)->where('vehicles.current_ignition',1)->get();
        $stationary_engine_off = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
        ->where('fm_devices.connection_state',1)->where('vehicles.current_movement',0)->where('vehicles.current_ignition',0)->get();
        $moving_engine_off = $a_vehicles->where('vehicles.current_movement',1)->where('vehicles.current_ignition',0)->get();
        $ia_vehicles = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
                                ->where('connection_state',0);
        $inactive_vehicles = $ia_vehicles->get();
        
        $moving_hour_ago = $ia_vehicles->where('vehicles.current_movement',1)->orderBy('vehicles.updated_at','DESC');
        $stationary_hour_ago = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
        ->where('connection_state',0)->where('vehicles.current_movement',0)->orderBy('vehicles.updated_at','DESC');
       
        return response()->json([
            "moving_active_vehicles" => $moving,
            "moving engine_off_active_vehicles" => $moving_engine_off,
            "stationary engine_on_active_vehicles" => $stationary_engine_on,
            "stationary engine_off_active_vehicles" => $stationary_engine_off,
            "moving hour ago_inactive_vehicles" => $moving_hour_ago->get(),
            "moving hour ago time_inactive_vehicles" => $moving_hour_ago->value('vehicles.updated_at'),
            "stationary hour ago_inactive_vehicles" => $stationary_hour_ago->get(),
            "stationary hour ago time_inactive_vehicles" => $stationary_hour_ago->value('vehicles.updated_at'),
        ]);
    }

    public function moving() {
        $moving_vehicles = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
                               ->leftJoin('users','vehicles.driver_id','=','users.user_id')
                               ->Join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id')
                               ->select('vehicle_id','vehicles.device_id','vehicle_code','vehicles.updated_at','device_serial','imei',
                               'sim_card_number','sim_card_serial','vehicle_plate_number','vehicles.odometer_current_reading', 'users.first_name',
                               'users.last_name','vehicles.current_speed','vehicle_types.vehicle_type')
                               ->where('fm_devices.connection_state',1)
                               ->where('vehicles.current_speed','!=',0)
                               ->get()->toArray();

                               return response()->json([
                                "moving_vehicles" => $moving_vehicles
                            ]);

    }

    public function stationary_engine_on() {
        $stationary_engine_on = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
                               ->leftJoin('users','vehicles.driver_id','=','users.user_id')
                               ->Join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id')
                               ->select('vehicle_id','vehicles.device_id','vehicles.driver_id','vehicle_code','vehicles.updated_at','device_serial','imei',
                               'sim_card_number','sim_card_serial','vehicle_plate_number', 'users.first_name',
                               'users.last_name','odometer_current_reading','vehicle_types.vehicle_type')
                               ->where('fm_devices.connection_state',1)
                               ->where('vehicles.current_speed',0)
                               ->where('vehicles.current_ignition',1)
                               ->get()->toArray();

                               return response()->json([
                                "stationary_engine_on" => $stationary_engine_on
                            ]);

    }

    public function stationary_engine_off() {
        $stationary_engine_off = Vehicle::join('fm_devices', 'vehicles.device_id', '=', 'fm_devices.device_id')
                               ->leftJoin('users','vehicles.driver_id','=','users.user_id')
                               ->Join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id')
                               ->select('vehicle_id','vehicles.device_id','vehicles.driver_id','vehicle_code','vehicles.updated_at','device_serial','imei',
                               'sim_card_number','sim_card_serial','vehicle_plate_number','users.first_name',
                               'users.last_name','odometer_current_reading','vehicle_types.vehicle_type')
                               ->where('fm_devices.connection_state',1)
                               ->where('vehicles.current_speed',0)
                               ->where('vehicles.current_ignition',0)
                               ->get()->toArray();

                               return response()->json([
                                "stationary_engine_off" => $stationary_engine_off
                            ]);

    }

    public function all_data(){
        $users = User::all()->count();
        $customers = Customer::all()->count();
        // $drivers = Driver::all()->count();
        $vehicles = Vehicle::all()->count();
        $zones = Location::all()->count();
        $roles_permissions = Role::all()->count();
        $events = Event::all()->count();
        $event_statuses = EventStatus::all()->count();
        return response()->json([
            "users" => $users,
            "customers" => $customers,
            "drivers" => $drivers,
            "vehicles" => $vehicles,
            "zones" => $zones,
            "roles_permissions" => $roles_permissions,
            "events" => $events,
            "event_statuses" => $event_statuses
        ]);
    }

    public function nonLinkedDevices($lnkd=1){
        $devices = \DB::table('fm_devices')
        ->select(
            'fm_devices.device_id',
            'vehicles.vehicle_id',
            'vehicles.driver_id',
            'users.first_name',
            'users.last_name',
            'vehicles.vehicle_plate_number',
            'fm_devices.imei',
            'vehicles.odometer_current_reading',
            'vehicles.current_latitude',
            'vehicles.current_longitude',
            'vehicles.current_speed',
            'vehicles.current_ignition',
            'vehicles.current_movement',
            'vehicles.current_mobile_latitude',
            'vehicle_types.vehicle_type',
            'vehicles.current_mobile_longitude'
        )
        ->leftJoin('vehicles','vehicles.device_id','=','fm_devices.device_id')
        ->leftJoin('users','vehicles.driver_id','=','users.user_id')
        ->Join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id')
        ->whereNull('vehicles.device_id');
        if(isset($lnkd) &&$lnkd == 5){
            return $devices->count();
        }
        return response()->json([
            "devices" => $devices->get()
        ]);
    }

    public function linkedDevices(){
        $devices = \DB::table('fm_devices')
        ->select(
            'fm_devices.device_id',
            'vehicles.vehicle_id',
            'vehicles.driver_id',
            'users.first_name',
            'users.last_name',
            'vehicles.odometer_current_reading',
            'vehicles.vehicle_plate_number',
            'fm_devices.imei',
            'vehicles.current_latitude',
            'vehicles.current_longitude',
            'vehicles.current_speed',
            'vehicles.current_ignition',
            'vehicles.current_movement',
            'vehicles.current_mobile_latitude',
            'vehicle_types.vehicle_type',
            'vehicles.current_mobile_longitude'
        )
        ->Join('vehicles','vehicles.device_id','=','fm_devices.device_id')
        ->leftJoin('users','vehicles.driver_id','=','users.user_id')
        ->Join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id');

        $unlinked = $this->nonLinkedDevices(5);
        $linked = $devices->count();
        $total_devices = $unlinked + $linked;
        $linked_devices_percentage = ($linked/$total_devices)*100;
        $unlinked_devices_percentage = ($unlinked/$total_devices)*100; 
        return response()->json([
            "devices" => $devices->get(),
            "linked_devices_percentage" => $linked_devices_percentage,
            "unlinked_devices_percentage" => $unlinked_devices_percentage,
        ]);

    }

    public function onlineDevices(){
        $devices = \DB::table('fm_devices')
        ->select(
            'fm_devices.device_id',
            'vehicles.vehicle_id',
            'vehicles.driver_id',
            'users.first_name',
            'users.last_name',
            'vehicles.odometer_current_reading',
            'vehicles.vehicle_plate_number',
            'fm_devices.imei',
            'vehicles.current_latitude',
            'vehicles.current_longitude',
            'vehicles.current_speed',
            'vehicles.current_ignition',
            'vehicles.current_movement',
            'vehicles.current_mobile_latitude',
            'vehicle_types.vehicle_type',
            'vehicles.current_mobile_longitude'
        )
        ->Join('vehicles','vehicles.device_id','=','fm_devices.device_id')
        ->leftJoin('users','vehicles.driver_id','=','users.user_id')
        ->Join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id')
        ->where('connection_state',1);

        $online_devices = $devices->count();
       
        return response()->json([
            "code" => 200,
            "data" => [
                "devices" => $devices->get(),
                "no_of_online_devices" => $online_devices
            ],
			"message" => "data fetched successfully"
        ]);

    }

    public function offlineDevices(){
        $devices = \DB::table('fm_devices')
        ->select(
            'fm_devices.device_id',
            'vehicles.vehicle_id',
            'vehicles.driver_id',
            'users.first_name',
            'users.last_name',
            'vehicles.odometer_current_reading',
            'vehicles.vehicle_plate_number',
            'fm_devices.imei',
            'vehicles.current_latitude',
            'vehicles.current_longitude',
            'vehicles.current_speed',
            'vehicles.current_ignition',
            'vehicles.current_movement',
            'vehicles.current_mobile_latitude',
            'vehicle_types.vehicle_type',
            'vehicles.current_mobile_longitude'
        )
        ->Join('vehicles','vehicles.device_id','=','fm_devices.device_id')
        ->leftJoin('users','vehicles.driver_id','=','users.user_id')
        ->Join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id')
        ->where('connection_state',0);

        $offline_devices = $devices->count();
       
        return response()->json([
            "code" => 200,
            "data" => [
                "devices" => $devices->get(),
                "no_of_offline_devices" => $offline_devices
            ],
			"message" => "data fetched successfully"
           
        ]);

    }

}