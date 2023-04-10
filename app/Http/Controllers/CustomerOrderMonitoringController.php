<?php
namespace App\Http\Controllers;
use Validator;
use Illuminate\Http\Request;


class CustomerOrderMonitoringController extends Controller {
    
    public function __construct()
    {
        //
    }

    public function index ($customerId,Request $request) {

        $validator = Validator::make([
            "customer_id" => $customerId
        ],[
            "customer_id" => "nullable|int|min:1|exists:customers,customer_id"
        ]);

        if ($validator->fails()) {
            return responseValidationError('Fields Validation Error.', $validator->errors());
        }
        $data =  $request->all();
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;
        $vehicle_ids = \App\Model\CustomerApprovedVehicle::where('customer_id', $customerId)->pluck('vehicle_id')->toArray();
       
        $vehicles =  \App\Model\Vehicle::with('driver:user_id,first_name,last_name', 'vehicle_type:vehicle_type_id,icon', 
            'vehicleStatus:status_id,title', 'device:device_id,connection_state', 'device.sensors.sensor_type', 'delivery_trips.trip_status:trip_status_id,trip_status_title,key')
            ->whereHas('delivery_trips.trip_status', function($query) {
                $query->where('key', 'ASSIGNED')->orWhere('key', 'STARTED');
            })
            /*->whereHas('device', function($query) {
            })
            ->whereHas('device.sensors', function($query) {
            })*/
            
            ->select('vehicle_id', 'vehicle_plate_number', 'last_updated_at','current_mobile_latitude' ,'current_mobile_longitude', 'priority', 'current_latitude', 'current_longitude', 'current_speed',
                'current_angle', 'current_altitude', 'engine_hours', 'odometer_current_reading', 'locked_statellites',
                'destination_latitude', 'destination_longitude', 'pos', 'srv', 'device_id', 'status_id', 'driver_id','vehicle_type_id')
            ->whereIn('vehicle_id', $vehicle_ids)->orderBy('updated_at');

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
            
              $vehicles = $vehicles->get()->toArray();

        // $vehicles = \App\Model\Order::select('vehicles.vehicle_id', 'delivery_trips.delivery_trip_id', 'orders.order_id', 'orders.customer_id')
        //     ->join('deliveries', 'deliveries.order_id', 'orders.order_id')
        //     ->join('delivery_trips', 'deliveries.delivery_trip_id', 'delivery_trips.delivery_trip_id')
        //     ->join('vehicles', 'delivery_trips.vehicle_id', 'vehicles.vehicle_id')
        //     ->where('orders.customer_id', $customerId)->get()->toArray();  

        //Ayesha 14-3-2022
        // $orders = \App\Model\Order::with('delivery.delivery_trip.vehicle.driver:user_id,first_name,last_name', 
        //     'delivery.delivery_trip.vehicle:vehicle_id,vehicle_plate_number,last_updated_at,current_latitude,current_longitude,current_speed,current_angle,current_altitude,engine_hours,odometer_current_reading,locked_statellites,destination_latitude,destination_longitude,pos,srv,status_id,device_id,driver_id,vehicle_type_id',
        //     'delivery.delivery_trip.vehicle.vehicleStatus:status_id,title', 'delivery.delivery_trip.vehicle.vehicle_type:vehicle_type_id,icon', 'delivery.delivery_trip.vehicle.device:device_id,connection_state',
        //     'delivery.delivery_trip.vehicle.device.sensors.sensor_type', 
        //     'order_status')
        //     ->whereHas('order_status', function($query) {
        //         $query->where('key', 'ASSIGNED');
        //     })->where('customer_id', $customerId)
        //     /*->select('order_number', 'vehicles.vehicle_plate_number', 'vehicles.last_updated_at', 'current_latitude', 'current_longitude', 'current_speed',
        //         'current_angle', 'current_altitude', 'engine_hours', 'odometer_current_reading', 'locked_statellites',
        //         'destination_latitude', 'destination_longitude', 'pos', 'srv', 'device_id', 'status_id', 'driver_id')
        //     ->*/->distinct()->get()->toArray();

        // $allOrders = [];
        // $i=0;
        // foreach ($orders as $order) {
        //     if (isset($order['delivery']) && isset($order['delivery']['delivery_trip']) &&  
        //         isset($order['delivery']['delivery_trip']['vehicle'])) {

        //         $data['order_number'] = $order['order_number'];
        //         $data['order_status_id'] = $order['order_status_id'];
        //         $data['vehicle_plate_number'] = $order['delivery']['delivery_trip']['vehicle']['vehicle_plate_number'];
        //         $data['last_updated_at'] = $order['delivery']['delivery_trip']['vehicle']['last_updated_at'];
        //         $data['current_latitude'] = $order['delivery']['delivery_trip']['vehicle']['current_latitude'];
        //         $data['current_longitude'] = $order['delivery']['delivery_trip']['vehicle']['current_longitude'];
        //         $data['current_speed'] = $order['delivery']['delivery_trip']['vehicle']['current_speed'];
        //         $data['current_angle'] = $order['delivery']['delivery_trip']['vehicle']['current_angle'];
        //         $data['current_altitude'] = $order['delivery']['delivery_trip']['vehicle']['current_altitude'];
        //         $data['engine_hours'] = $order['delivery']['delivery_trip']['vehicle']['engine_hours'];
        //         $data['odometer_current_reading'] = $order['delivery']['delivery_trip']['vehicle']['odometer_current_reading'];
        //         $data['locked_statellites'] = $order['delivery']['delivery_trip']['vehicle']['locked_statellites'];
        //         $data['destination_latitude'] = $order['delivery']['delivery_trip']['vehicle']['destination_latitude'];
        //         $data['destination_longitude'] = $order['delivery']['delivery_trip']['vehicle']['destination_longitude'];
        //         $data['pos'] = $order['delivery']['delivery_trip']['vehicle']['pos'];
        //         $data['srv'] = $order['delivery']['delivery_trip']['vehicle']['srv'];
        //         $data['device_id'] = $order['delivery']['delivery_trip']['vehicle']['device_id'];
        //         $data['status_id'] = $order['delivery']['delivery_trip']['vehicle']['status_id'];
        //         $data['driver_id'] = $order['delivery']['delivery_trip']['vehicle']['driver_id'];
        //         $data['vehicle_type_id'] = $order['delivery']['delivery_trip']['vehicle']['vehicle_type_id'];

        //         if (isset($order['delivery']['delivery_trip']['vehicle']['driver'])) {
        //             $data['driver'] = $order['delivery']['delivery_trip']['vehicle']['driver'];
        //         }

        //         if (isset($order['delivery']['delivery_trip']['vehicle']['vehicle_type'])) {
        //             $data['vehicle_type'] = $order['delivery']['delivery_trip']['vehicle']['vehicle_type'];
        //         }

        //         if (isset($order['delivery']['delivery_trip']['vehicle']['device'])) {
        //             $data['device'] = $order['delivery']['delivery_trip']['vehicle']['device'];
                    
        //             if (isset($order['delivery']['delivery_trip']['vehicle']['device']['sensors'])) {
        //                 $data['device']['sensors'] = $order['delivery']['delivery_trip']['vehicle']['device']['sensors'];
        //             }
        //         }
        //         array_push($allOrders, $data);
        //     }
        // }

        return response()->json([
            "code" => 200,
            "data" => $vehicles
        ]);
    }
}