<?php

namespace App\Http\Controllers;

use App\Model\Address;
use App\Model\Constraints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Validator;
use DB;
use Illuminate\Validation\Rule;
use App\Model\Store as Store;
use App\Model\SapApi;
use App\Model\StoreConstraints as StoreConstraints;
use App\Model\TripLogs as TripLogs;
use App\Model\Delivery as Delivery;
use App\Model\DeliveryTrip as DeliveryTrip;
use App\Model\PickupMaterial as PickupMaterial;
use App\Model\DropoffMaterial as DropoffMaterial;
use App\Model\OrderStatus;
use App\Model\TripStatus as TripStatus;
use App\Model\Vehicle as Vehicle;
use App\Model\VehicleType as VehicleType;
use App\Model\Category as Category;
use App\Model\VehicleCategory as VehicleCategory;
use App\Model\OrderLogs as OrderLogs;
use App\Model\OrderItem as OrderItem;
use App\Model\VehicleStocks as VehicleStocks;
use App\Model\Route as Route;
use DateTime;
use Carbon\Carbon;
use App\Model\Order as Order;
use App\Model\TripBatches as TripBatches;
use App\Model\User as User;
use App\Model\OrderMaterial;
use App\Model\Material;
use App\Model\Customer;
use App\Model\Unit;
use App\Model\TripAssignedMaterial;
use App\Message\Error;

use Auth;
use Illuminate\Validation\Rules\Exists;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class TripsController extends Controller

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
    public function Updatesuggested_path(Request $request, $delivery_trip_id){
        $data = $request->all();
        $validator = Validator::make($data,[
           'suggested_path'  => 'required',
        ]);
        if($validator->fails()){
         return respondWithSuccess(null, "TRIPS", "", "Suggested Path Updated");
        }
        $data = DeliveryTrip::findOrFail($delivery_trip_id);
        $data->suggested_path = $request->suggested_path;
        if($data->save()){
            return response()->json([
                'code'=>200,
                'message'=>'Suggested Path Updated'
            ]);
        }
    }
    function getTripsAction(Request $request, $date, $store_id)
    {


        $validatedData = Validator::make(['date' => $date], [
            'date' => 'required|date|date_format:Y-m-d',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                "code" => 500,
                "message" => "Date is invalid"
            ]);
        }

        try {

            Store::where('store_id', $store_id);
        } catch (\Exception $ex) {
            return response()->json([
                "code" => 500,
                "message" => "Couldn't find warehouse with your given id"
            ]);
        }

        $data = [];
        $trip_ids = [];

        $deliverytripids = DeliveryTrip::where('order_id', '!=', NULL)
            ->get('delivery_trip_id')->toArray();
            
        //DeliveryTrips with vehicles having driver id not null
        $trips = DeliveryTrip::whereHas('vehicle', function($q) {
            $q->where('driver_id','!=', null);
          })
                ->with(
            [
                'trip_status',
                'vehicle.user',
                'vehicle.vehicle_type',
                'vehicle.vehicle_category'
            ]
        )
            ->whereIn('delivery_trip_id', $deliverytripids)
            ->where('store_id', $store_id)
            ->whereBetween('trip_date', [$date . " 00:00:00", $date . " 23:59:59"])
            ->where('status', 1)
            ->get()->toArray();



        for ($i = 0, $count = count($trips); $i < $count; $i++) {

            @$trip_ids[$trips[$i]['vehicle_id']][] = $trips[$i]['delivery_trip_id'];
            $trip_date = date_create($trips[$i]['trip_date']);
            $created_date = date_create($trips[$i]['created_at']);
            $trip_startime = ($trips[$i]['trip_startime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_startime']), "Y-m-d h:i:a");
            $trip_endtime = ($trips[$i]['trip_endtime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_endtime']), "Y-m-d h:i:a");


            if($trips[$i]['pickup_service_time'] != null && $trips[$i]['dropoff_service_time'] != null)
{

    $secs = strtotime($trips[$i]['pickup_service_time'])-strtotime("00:00:00");
    $serivicetime = date("H:i:s",strtotime($trips[$i]['dropoff_service_time'])+$secs);

    
}
else {
    $serivicetime=NULL;
}

if(isset($trips[$i]['vehicle']['user']['first_name']) && $trips[$i]['vehicle']['user']['last_name'])
{
    $temp_name = $trips[$i]['vehicle']['user']['first_name'] . ' ' . $trips[$i]['vehicle']['user']['last_name'];
}
else {
    $temp_name=NULL;
}


            $data[] = [
                'delivery_trip_id' => $trips[$i]['delivery_trip_id'],
                'trip_code' => $trips[$i]['trip_code'],
                'suggested_path' => $trips[$i]['suggested_path'],
                'trip_type' => $trips[$i]['delivery_trip_type'],
                'trip_date' => date_format($trip_date, "Y-m-d"),
                'created_at' => $trips[$i]['created_at'],
                'trip_startime' => $trip_startime,
                'trip_endtime' => $trip_endtime,
                'trip_service_time' => $serivicetime,
             
                
                'trip_status' => json_decode($trips[$i]['trip_status']['trip_status_title'], true),
   
                'trip_status_key' => $trips[$i]['trip_status']['key'],
               
                'calculated_time' => ($trips[$i]['total_time'] == NULL) ? "" : $trips[$i]['total_time'],
                'calculated_dist' => ($trips[$i]['total_distance'] == NULL) ? "" : round($trips[$i]['total_distance'], 2),
                'vehicle_id' => $trips[$i]['vehicle']['vehicle_id'],
                'vehicle_type_id' => $trips[$i]['vehicle']['vehicle_type_id'],
              
                'vehicle_type' => (json_decode($trips[$i]['vehicle']['vehicle_type']['vehicle_type'], true) == NULL) ? "" : json_decode($trips[$i]['vehicle']['vehicle_type']['vehicle_type'], true),
                'vehicle_type_key' => $trips[$i]['vehicle']['vehicle_type']['key'],
                'vehicle_category_id' => $trips[$i]['vehicle']['vehicle_category_id'],
                'vehicle_category' => json_decode($trips[$i]['vehicle']['vehicle_category']['vehicle_category'], true),
                'vehicle_category_key' => $trips[$i]['vehicle']['vehicle_category']['key'],
                'vehicle_opening_odometer' => ($trips[$i]['vehicle']['vehicle_opening_odometer'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_opening_odometer'],
                'vehicle_code' => ($trips[$i]['vehicle']['vehicle_code'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_code'],
            
                'speed' => (json_decode($trips[$i]['vehicle']['speed'], true) == NULL) ? "" : json_decode($trips[$i]['vehicle']['speed'], true),
                'vehicle_plate_number' => $trips[$i]['vehicle']['vehicle_plate_number'],


                'driver' => [
                    'user_id' => $trips[$i]['vehicle']['user']['user_id'],
                    'name' => $temp_name,
                    'phone' => $trips[$i]['vehicle']['user']['phone'],
                ],
            ];
        }
        if (count($data) > 0) {
            return response()->json([
                "code" => 200,
                "data" => array_values($data),
                "message" => __("Trips Loaded!")
            ]);
        } else {
            return response()->json([
                "code" => 203,
                "data" => array_values($data),
                "message" => __("No Trips Loaded!")
            ]);
        }
    }

    function getCustomerTripsAction(Request $request, $date, $customer_id)
    {

        $validatedData = Validator::make(['date' => $date], [
            'date' => 'required|date|date_format:Y-m-d',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                "code" => 500,
                "message" => "Date is invalid"
            ]);
        }

        try {

            \App\Model\Customer::where('customer_id', $customer_id);
        } catch (\Exception $ex) {
            return response()->json([
                "code" => 500,
                "message" => "Couldn't find customer with your given id"
            ]);
        }

        $data = [];
        $trip_ids = [];

        // $deliverytripids = Delivery::join('orders', 'orders.order_id', '=', 'deliveries.order_id')
        //     ->where('deliveries.order_id', '!=', NULL)
        //     ->get('deliveries.delivery_trip_id')->toArray();

        $trips = DeliveryTrip::with(
            [
                'trip_status',
                'vehicle.driver',
                'vehicle.vehicle_type',
                'vehicle.vehicle_category'
            ]
        )
            ->where('order_id', '!=', NULL)
            ->whereBetween('trip_date', [$date . " 00:00:00", $date . " 23:59:59"])
            ->where('status', 1)
            ->get()->toArray();



        for ($i = 0, $count = count($trips); $i < $count; $i++) {

            @$trip_ids[$trips[$i]['vehicle_id']][] = $trips[$i]['delivery_trip_id'];
            $trip_date = date_create($trips[$i]['trip_date']);
            $created_date = date_create($trips[$i]['created_at']);
            $trip_startime = ($trips[$i]['trip_startime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_startime']), "Y-m-d h:i:a");
            $trip_endtime = ($trips[$i]['trip_endtime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_endtime']), "Y-m-d h:i:a");


            $temp_name = $trips[$i]['vehicle']['driver']['name'];
            $data[] = [
                'delivery_trip_id' => $trips[$i]['delivery_trip_id'],
                'trip_code' => $trips[$i]['trip_code'],
                'trip_type' => $trips[$i]['delivery_trip_type'],
                'trip_date' => date_format($trip_date, "Y-m-d"),
                'created_at' => $trips[$i]['created_at'],
                'service_time' => ($trips[$i]['service_time'] == NULL) ? "" : $trips[$i]['service_time'],
                'trip_cost' => ($trips[$i]['gas_cost'] == NULL) ? "" : round($trips[$i]['gas_cost'], 2),
                'trip_startime' => $trip_startime,
                'trip_endtime' => $trip_endtime,
                'trip_service_time' => ($trips[$i]['service_time'] == NULL) ? "" : $trips[$i]['service_time'],
                'trip_status' => json_decode($trips[$i]['trip_status']['trip_status_title'], true),
                'trip_status_key' => $trips[$i]['trip_status']['key'],
                'total_deliveries' => count($trips[$i]['deliveries']),
                'estimated_time' => ($trips[$i]['google_time'] == NULL) ? "" : $trips[$i]['google_time'],
                'estimated_dist' => ($trips[$i]['google_dist'] == NULL) ? "" : $trips[$i]['google_dist'],
                'calculated_time' => ($trips[$i]['total_time'] == NULL) ? "" : round($trips[$i]['total_time'], 2),
                'calculated_dist' => ($trips[$i]['total_distance'] == NULL) ? "" : round($trips[$i]['total_distance'], 2),
                'is_cancellable' => ($trips[$i]['trip_endtime'] == NULL) ? "True" : "False",
                'is_editable' => ($trips[$i]['trip_startime'] == NULL) ? "True" : "False",
                'route_name' =>   isset($trips[$i]['route']['route_name']) ? json_decode($trips[$i]['route']['route_name']) : "",
                'route_id' =>   isset($trips[$i]['route']['route_id']) ? $trips[$i]['route']['route_id'] : "",
                'vehicle_id' => $trips[$i]['vehicle']['vehicle_id'],
                'vehicle_type_id' => $trips[$i]['vehicle']['vehicle_type_id'],
                'vehicle_type' => (json_decode($trips[$i]['vehicle']['vehicle_type']['vehicle_type'], true) == NULL) ? "" : json_decode($trips[$i]['vehicle']['vehicle_type']['vehicle_type'], true),
                'vehicle_type_key' => $trips[$i]['vehicle']['vehicle_type']['key'],
                'is_Approved' => ($trips[$i]['is_approved'] == true) ? "True" : "False",
                'vehicle_category_id' => $trips[$i]['vehicle']['vehicle_category_id'],
                'vehicle_category' => json_decode($trips[$i]['vehicle']['vehicle_category']['vehicle_category'], true),
                'vehicle_category_key' => $trips[$i]['vehicle']['vehicle_category']['key'],
                'vehicle_opening_odometer' => ($trips[$i]['vehicle']['vehicle_opening_odometer'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_opening_odometer'],
                'barcode' => ($trips[$i]['vehicle']['barcode']  == NULL) ? "" : $trips[$i]['vehicle']['barcode'],
                'vehicle_code' => ($trips[$i]['vehicle']['vehicle_code'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_code'],

                'speed' => (json_decode($trips[$i]['vehicle']['speed'], true) == NULL) ? "" : json_decode($trips[$i]['vehicle']['speed'], true),
                'vehicle_plate_number' => $trips[$i]['vehicle']['vehicle_plate_number'],


                'driver' => [
                    'user_id' => $trips[$i]['vehicle']['driver']['user_id'],
                    'name' => $temp_name,
                    'phone' => $trips[$i]['vehicle']['driver']['phone'],
                    'profile_image' => ($trips[$i]['vehicle']['driver']['profile_image'] == NULL) ? "" : $trips[$i]['vehicle']['driver']['profile_image']
                ],
            ];
        }
        if (count($data) > 0) {
            return response()->json([
                "code" => 200,
                "data" => array_values($data),
                "message" => __("Trips Loaded!")
            ]);
        } else {
            return response()->json([
                "code" => 204,
                "data" => array_values($data),
                "message" => __("No Trips Loaded!")
            ]);
        }
    }

    function getTripDefaultsAction(Request $request, $store_id)
    {

        $data = $request->json()->all();
        $trips_statuses = TripStatus::orderBy('trip_status_id', 'ASC')->get()->toArray();

        $store_vehicle_types = VehicleType::orderBy('vehicle_type_id', 'ASC')->get()->toArray();
        $store_vehicles = Vehicle::with('driver', 'vehicle_type', 'delivery_trips.order');

        /*->where('store_id',$store_id)*/

        if (isset($data['customer_id'])) {
            $store_vehicles->whereHas('delivery_trips.order', function ($query) use ($data) {
                return $query->where('customer_id', $data['customer_id']);
            });
        }

        $store_vehicles = $store_vehicles->get()->toArray();

        $store_vehicle_categories = VehicleCategory::orderBy('vehicle_category_id', 'ASC')->get()->toArray();
        $store_routes = []; //Route::where('store_id',$store_id)->get()->toArray();
        $driver = [];
        $vehicles = [];
        $tripStatuses = [];
        $vehicleCategory = [];
        $vehicleType = [];
        $storeRoutes = [];


        if (count($store_vehicles) > 0) {
            for ($i = 0, $count = count($store_vehicles); $i < $count; $i++) {
                $vehicles[] = [
                    'vehicle_id' => $store_vehicles[$i]['vehicle_id'],
                    'vehicle_code' => $store_vehicles[$i]['vehicle_code'],
                    'vehicle_plate' => $store_vehicles[$i]['vehicle_plate_number'],
                    'vehicle_type' => isset($store_vehicles[$i]['vehicle_type']['vehicle_type']) ? json_decode($store_vehicles[$i]['vehicle_type']['vehicle_type'], true) : "",
                ];

                if ($store_vehicles[$i]['driver'] !== NULL) //in case driver is not attached with vehicle.
                {
                    //dd($store_vehicles[$i]);
                    $key = array_search($store_vehicles[$i]['driver']['user_id'], array_column($driver, 'driver_id'));

                  
                    if (isset($store_vehicles[$i]['driver']['first_name']) || isset($store_vehicles[$i]['driver']['last_name'])) {

                        $store_vehicles[$i]['driver']['first_name'] = isset($store_vehicles[$i]['driver']['first_name']) && $store_vehicles[$i]['driver']['first_name'] != null ? $store_vehicles[$i]['driver']['first_name'] : null;
                        $store_vehicles[$i]['driver']['last_name'] = isset($store_vehicles[$i]['driver']['last_name']) && $store_vehicles[$i]['driver']['last_name'] != null ? $store_vehicles[$i]['driver']['last_name'] : null;    
                        $temp_name = '{"en":"' . $store_vehicles[$i]['driver']['first_name'] . ' ' . $store_vehicles[$i]['driver']['last_name'] . '","ar":"' . $store_vehicles[$i]['driver']['first_name'] .
                            ' ' . $store_vehicles[$i]['driver']['last_name'] . '"}';
                    }
                    else {
                        $temp_name = $store_vehicles[$i]['driver']['name'];
                    }

                    if (!$key) {
                        $driver[] = [

                            'driver_id' => $store_vehicles[$i]['driver']['user_id'],
                            'name' => json_decode($temp_name, true),

                        ];
                    }
                }
            }
        }
        if (count($trips_statuses) > 0) {
            for ($i = 0, $count = count($trips_statuses); $i < $count; $i++) {
                $tripStatuses[] = [
                    'trip_status_id' => $trips_statuses[$i]['trip_status_id'],
                    'trip_status' => json_decode($trips_statuses[$i]['trip_status_title'], true),
                    'trip_status_key' => $trips_statuses[$i]['key'],
                ];
            }
        }

        if (count($store_vehicle_categories) > 0) {
            for ($i = 0, $count = count($store_vehicle_categories); $i < $count; $i++) {
                $vehicleCategory[] = [
                    'category_id' => $store_vehicle_categories[$i]['vehicle_category_id'],
                    'category_name' => json_decode($store_vehicle_categories[$i]['vehicle_category'], true),
                    'category_key' => $store_vehicle_categories[$i]['key'],
                ];
            }
        }

        if (count($store_vehicle_types) > 0) {
            for ($i = 0, $count = count($store_vehicle_types); $i < $count; $i++) {
                $vehicleType[] = [
                    'vehicle_type_id' => $store_vehicle_types[$i]['vehicle_type_id'],
                    'type_name' => (json_decode($store_vehicle_types[$i]['vehicle_type'], true) == NULL) ? "" : json_decode($store_vehicle_types[$i]['vehicle_type'], true),
                    'type_key' => $store_vehicle_types[$i]['key'],
                ];
            }
        }

        if (count($store_routes) > 0) {
            for ($i = 0, $count = count($store_routes); $i < $count; $i++) {
                $storeRoutes[] = [
                    'route_id' => $store_routes[$i]['route_id'],
                    'route_name' => (json_decode($store_routes[$i]['route_name'], true) == NULL) ? "" : json_decode($store_routes[$i]['route_name'], true),
                    'route_code' => $store_routes[$i]['route_code'],
                ];
            }
        }

        return response()->json([
            "code" => 200,
            "data" => [
                'store_routes' => $storeRoutes,
                'vehicle_types' => $vehicleType,
                'vehicle_categories' =>  $vehicleCategory,
                'trip_statuses' => $tripStatuses,
                'drivers' => $driver,
                'vehicles' => $vehicles
            ],
            "message" => __("Listing Defaults Loaded!")
        ]);
    }

    function getCustomerTripDefaultsAction(Request $request, $customer_id)
    {
        $validator = Validator::make([

            'customer_id' => $customer_id
        ], [
            'customer_id' => 'nullable|int|min:1|exists:customers,customer_id'
        ]);

        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $trips_statuses = TripStatus::orderBy('trip_status_id', 'ASC')->get()->toArray();
        $sites = \App\Model\Address::with('type')->where('customer_id', $customer_id)->orderBy('created_at', 'desc')->get()->toArray();

        $store_vehicle_types = VehicleType::orderBy('vehicle_type_id', 'ASC')->get()->toArray();

        $vehicle_ids = \App\Model\Vehicle::whereHas('delivery_trips.order.customer', function ($q) use ($customer_id) {
            $q->where('customer_id',$customer_id);
          })->pluck('vehicle_id')->toArray();
        


        $store_vehicles = Vehicle::with('driver', 'vehicle_type', 'delivery_trips.order')->whereIn('vehicle_id', $vehicle_ids)->get()->toArray();

        $store_vehicle_categories = VehicleCategory::orderBy('vehicle_category_id', 'ASC')->get()->toArray();
        $driver = [];
        $vehicles = [];
        $tripStatuses = [];
        $vehicleCategory = [];
        $vehicleType = [];
        $storeRoutes = [];
        $customerSites = [];


        if (count($store_vehicles) > 0) {
            for ($i = 0, $count = count($store_vehicles); $i < $count; $i++) {
                $vehicles[] = [
                    'vehicle_id' => $store_vehicles[$i]['vehicle_id'],
                    'vehicle_code' => $store_vehicles[$i]['vehicle_code'],
                    'vehicle_plate' => $store_vehicles[$i]['vehicle_plate_number'],
                    'vehicle_type' => isset($store_vehicles[$i]['vehicle_type']['vehicle_type']) ? json_decode($store_vehicles[$i]['vehicle_type']['vehicle_type'], true) : "",
                ];

                if ($store_vehicles[$i]['driver'] !== NULL) //in case driver is not attached with vehicle.
                {
                    //dd($store_vehicles[$i]);
                    $key = array_search($store_vehicles[$i]['driver']['user_id'], array_column($driver, 'driver_id'));

                    if (!isset($store_vehicles[$i]['driver']['name'])) {

                        $temp_name = '{"en":"' . $store_vehicles[$i]['driver']['first_name'] . ' ' . $store_vehicles[$i]['driver']['last_name'] . '","ar":"' . $store_vehicles[$i]['driver']['first_name'] .
                            ' ' . $store_vehicles[$i]['driver']['last_name'] . '"}';
                    } else {
                        $temp_name = $store_vehicles[$i]['driver']['name'];
                    }

                    if (!$key) {
                        $driver[] = [

                            'driver_id' => $store_vehicles[$i]['driver']['user_id'],
                            'name' => json_decode($temp_name, true),

                        ];
                    }
                }
            }
        }

        if (count($trips_statuses) > 0) {
            for ($i = 0, $count = count($trips_statuses); $i < $count; $i++) {
                $tripStatuses[] = [
                    'trip_status_id' => $trips_statuses[$i]['trip_status_id'],
                    'trip_status' => json_decode($trips_statuses[$i]['trip_status_title'], true),
                    'trip_status_key' => $trips_statuses[$i]['key'],
                ];
            }
        }

        if (count($store_vehicle_categories) > 0) {
            for ($i = 0, $count = count($store_vehicle_categories); $i < $count; $i++) {
                $vehicleCategory[] = [
                    'category_id' => $store_vehicle_categories[$i]['vehicle_category_id'],
                    'category_name' => json_decode($store_vehicle_categories[$i]['vehicle_category'], true),
                    'category_key' => $store_vehicle_categories[$i]['key'],
                ];
            }
        }

        if (count($store_vehicle_types) > 0) {
            for ($i = 0, $count = count($store_vehicle_types); $i < $count; $i++) {
                $vehicleType[] = [
                    'vehicle_type_id' => $store_vehicle_types[$i]['vehicle_type_id'],
                    'type_name' => (json_decode($store_vehicle_types[$i]['vehicle_type'], true) == NULL) ? "" : json_decode($store_vehicle_types[$i]['vehicle_type'], true),
                    'type_key' => $store_vehicle_types[$i]['key'],
                ];
            }
        }

        if (count($sites) > 0) {
            for ($i=0; $i < count($sites); $i++) {
                $customerSites[] = [
                    'address_id' => $sites[$i]['address_id'],
                    'type' => ($sites[$i]['type']) ? json_decode($sites[$i]['type']['name'], true) : "",
                    'address_title' => $sites[$i]['address_title'],
                    'address' => $sites[$i]['address'],
                ];
            }
        }

        return response()->json([
            "code" => 200,
            "data" => [
                'store_routes' => $storeRoutes,
                'vehicle_types' => $vehicleType,
                'vehicle_categories' =>  $vehicleCategory,
                'trip_statuses' => $tripStatuses,
                'drivers' => $driver,
                'vehicles' => $vehicles,
                'sites' => $customerSites,
            ],
            "message" => __("Listing Defaults Loaded!")
        ]);

        return;
    }
    function getTripListingDetails(Request $request, $store_id){
        $parameterArray =  json_decode($request->get("data"), true);
        if ($parameterArray != NULL || $parameterArray != null) {
            $validator = Validator::make([
                'fdate' => @$parameterArray['startDate'],
                'todate' => @$parameterArray['endDate'],
                'store_id' => $store_id,
                'status' => @$parameterArray['status'],
                'driver' => @$parameterArray['driver'],
                'trip_type' => @$parameterArray['tripType'],
                'vehicle' => @$parameterArray['vehicle'],
                'vehicle_category' => @$parameterArray['vehicleCategory'],
                'vehicle_type' => @$parameterArray['vehicleType'],
                'address_id' => @$parameterArray['address_id'],
            ], [
                'fdate' => 'date|date_format:Y-m-d|nullable',
                'todate' => 'date|date_format:Y-m-d|nullable',
                'store_id' => 'required|int|min:1',
                'status' => 'nullable|string',
                'driver' => 'nullable|int',
                'trip_type' => ['nullable', Rule::in(['Custom', 'Dynamic', 'Static'])],
                'vehicle' => 'nullable|int',
                'vehicle_category' => 'nullable|string',
                'vehicle_type' => 'nullable|string',
                'batch_no' => 'nullable|string|min:1',
                'customer_id' => 'nullable|string|min:1',
                'address_id' => 'nullable|string|min:1'
            ]);
        } elseif ($parameterArray == NULL || $parameterArray == null) {
            return response()->json([
                'status' => 'error',
                'code' => '300',
                'message' => 'Query Parameters Missing!'
            ]);
        } else {
            $validator = Validator::make(['sal_off_id' => $store_id], [
                'store_id' => 'required|int|min:1'
            ]);
        }
        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        try {
            Store::where('store_id', $store_id);
        } catch (\Exception $ex) {
            return response()->json([
                "code" => 500,
                "message" => "Couldn't find warehouse with your given id"
            ]);
        }
        $deliverytripids = DeliveryTrip::distinct('delivery_trip_id')->select('delivery_trip_id');
        // dd($deliverytripids);
        // $total_count = 0;
        // $total_count = count($deliverytripids->get());
        // $offset = $limit = 0;
        // if(!empty($parameterArray['offset'])){ $offset = $parameterArray['offset']; $deliverytripids = $deliverytripids->skip($offset); }
        // if(!empty($parameterArray['totalRecords'])){ $limit = $parameterArray['totalRecords']; $deliverytripids = $deliverytripids->take($limit); }
        $deliverytripids = $deliverytripids->get()->toArray();
        // dd($deliverytripids);

        $trips = DeliveryTrip::with([
            'trip_status',
            'vehicle.user',
            'vehicle.vehicle_type',
            'vehicle.vehicle_category',
            'order',
            'order.category',
            'order.address',
            'order.site_location',
            'order.shipping_address',
        ])->whereIn('delivery_trip_id', $deliverytripids);

      
  
        if ($parameterArray != null || $parameterArray != NULL) {
            if (isset($parameterArray['status']) && ($parameterArray['status'] != '' || $parameterArray['status'] != NULL || $parameterArray['status'] != null)) {

                $tripStatus = $parameterArray['status'];

                $trips = $trips->whereHas('trip_status', function ($query) use ($tripStatus) {
                    $query->where('key', $tripStatus);
                });
            }

            if (isset($parameterArray['approveDD']) && ($parameterArray['approveDD'] != '' || $parameterArray['approveDD'] != NULL || $parameterArray['approveDD'] != null)) {


                if ($parameterArray['approveDD'] == 'false') {

                    $tripApprove = $parameterArray['approveDD'];
                    $converted_res = $tripApprove ? 'false' : 'true';

                    $trips = $trips->where('is_approved', $converted_res);
                } else if ($parameterArray['approveDD'] == 'true') { {

                        $tripApprove = $parameterArray['approveDD'];
                        $converted_res = $tripApprove ? 'true' : 'false';


                        $trips = $trips->where('is_approved', $converted_res);
                    }
                }
            }

            if (isset($parameterArray['tripType']) && ($parameterArray['tripType'] != '' || $parameterArray['tripType'] != NULL || $parameterArray['tripType'] != null)) {

                $tripType = $parameterArray['tripType'];
                $trips = $trips->where('delivery_trip_type', $tripType);
            }


            //vehicel_category_key
            if (isset($parameterArray['vehicleCategory']) && ($parameterArray['vehicleCategory'] != '' || $parameterArray['vehicleCategory'] != NULL || $parameterArray['vehicleCategory'] != null)) {

                $vehicleCategory = $parameterArray['vehicleCategory'];
                $trips = $trips->whereHas('vehicle.vehicle_category', function ($query) use ($vehicleCategory) {
                    $query->where('key', $vehicleCategory);
                });
            }

            //vehicel_type_key
            if (isset($parameterArray['vehicleType']) && ($parameterArray['vehicleType'] != '' || $parameterArray['vehicleType'] != NULL || $parameterArray['vehicleType'] != null)) {

                $vehicleType = $parameterArray['vehicleType'];
                $trips = $trips->whereHas('vehicle.vehicle_type', function ($query) use ($vehicleType) {
                    $query->where('key', $vehicleType);
                });
            }
            //drive_id
            if (isset($parameterArray['driver']) && ($parameterArray['driver'] != '' || $parameterArray['driver'] != NULL || $parameterArray['driver'] != null)) {

                $vehicleDriver = $parameterArray['driver'];

                $trips = $trips->whereHas('vehicle.driver', function ($query) use ($vehicleDriver) {
                    $query->where('user_id', $vehicleDriver);
                });
            }
            //vehicle_id
            if (isset($parameterArray['vehicle']) && ($parameterArray['vehicle'] != '' || $parameterArray['vehicle'] != NULL || $parameterArray['vehicle'] != null)) {

                $vehicle = $parameterArray['vehicle'];
                $trips = $trips->whereHas('vehicle', function ($query) use ($vehicle) {
                    $query->where('vehicle_id', $vehicle);
                });
            }

            //batch_number
            if (isset($parameterArray['batch_no']) && ($parameterArray['batch_no'] != '' || $parameterArray['batch_no'] != NULL || $parameterArray['batch_no'] != null)) {

                $batchNo = $parameterArray['batch_no'];
                $trips = $trips->where('batch_no', $batchNo);
            }
            if (isset($parameterArray['delivery_trip_id']) && ($parameterArray['delivery_trip_id'] != '' || $parameterArray['delivery_trip_id'] != NULL || $parameterArray['delivery_trip_id'] != null)) {

                $deliverytripid = $parameterArray['delivery_trip_id'];

                $trips = $trips->where('delivery_trip_id', $deliverytripid);
            }


            //trip_date
            if (isset($parameterArray['startDate']) && ($parameterArray['startDate'] != '' || $parameterArray['startDate'] != NULL || $parameterArray['startDate'] != null) && isset($parameterArray['endDate']) && ($parameterArray['endDate'] != '' || $parameterArray['endDate'] != NULL || $parameterArray['endDate'] != null)) {

                $from = $parameterArray['startDate'];
                $to = $parameterArray['endDate'];
                $trips = $trips->whereBetween(\DB::raw('DATE(trip_date)'), array($from, $to));
            }

            //customer
            if (isset($parameterArray['customer_id']) && ($parameterArray['customer_id'] != '' || $parameterArray['customer_id'] != NULL || $parameterArray['customer_id'] != null)) {
                $customer_id = $parameterArray['customer_id'];

                $trips = $trips->whereHas('order', function ($query) use ($customer_id) {
                    $query->where('customer_id', $customer_id);
                });
            }

            //address, pickup, site location, shipping address
            if (isset($parameterArray['address_id']) && ($parameterArray['address_id'] != '' || $parameterArray['address_id'] != NULL || $parameterArray['address_id'] != null)) {
                $address_id = $parameterArray['address_id'];

                $trips = $trips->whereHas('order', function($query) use ($address_id) {
                    $query->where('pickup_address_id', $address_id)->orWhere('shipping_address_id', $address_id)->orWhere('site_location', $address_id);
                });
            }
        }

        $trips = $trips->where('status', 1)->orderBy('created_at', 'DESC')->get()->toArray();
        $vehicles = [];
        $trip_ids = [];

        for ($i = 0, $count = count($trips); $i < $count; $i++) {

            $trip_date = date_create($trips[$i]['trip_date']);
            $created_date = ($trips[$i]['created_at'] == NULL) ? "" : date_format(date_create($trips[$i]['created_at']), "Y-m-d H:i:s");
            $trip_startime = ($trips[$i]['trip_startime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_startime']), "Y-m-d H:i:s");
            $trip_endtime = ($trips[$i]['trip_endtime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_endtime']), "Y-m-d H:i:s");
            $temp_name = "";

            if (isset($trips[$i]['vehicle']['user'])) {
                $userid = $trips[$i]['vehicle']['user']['user_id'];

                $username = $trips[$i]['vehicle']['user'];

                if (!isset($username['name'])) {

                    $temp_name = '{"en":"' . $username['first_name'] . ' ' . $username['last_name'] . '","ar":"' . $username['first_name'] .
                        ' ' . $username['last_name'] . '"}';
                    $temp_name = json_decode($temp_name);
                } else {
                    $temp_name = $username['name'];
                }
            }
            if($trips[$i]['trip_status']['key'] != "CLOSED" && $trips[$i]['trip_status']['key'] != "ASSIGNED")
            {
                $mytime = Carbon::now();
                $starttime=$trip_startime;
                $starttime = Carbon::createFromFormat('Y-m-d H:i:s', $starttime);
               
                $diff = $starttime->diffInMinutes($mytime);
                $trip_Time = gmdate("H:i:s", ($diff * 60));
               
                $getLoc=DB::table('vehicle_locations')->where('delivery_trip_id',$trips[$i]['delivery_trip_id'])
                ->pluck('vehLoc')->toArray();

                if(is_array($getLoc) && count($getLoc) > 0)
                {
                    $all_data = json_decode($getLoc[0]);
                    $index=count($all_data);
                    $index=$index-1;
                    $all_data= $all_data[$index];
            
                    $lat2=$all_data->lat;
                    $lng2=$all_data->lng;
                    $lat=($trips[$i]['start_latitude'] == NULL) ? "" : $trips[$i]['start_latitude'];
                    $lng=($trips[$i]['start_longitude'] == NULL) ? "" : $trips[$i]['start_longitude'];
                
                    if($lat == NULL||$lng == NULL || $lat2 == NULL || $lng2 == NULL )
                    {
                        $trip_dist= 0;
                    }
                    else {
                        $trip_dist= _getDistance($lat,$lng,$lat2,$lng2);

                    }
                }
                else {
                    $trip_dist=0;

                }
           
            }
            else 
            {
               

               $trip_Time = $trips[$i]['actual_time'] == NULL ? "" : $trips[$i]['actual_time'];
              $trip_dist=($trips[$i]['actual_distance'] == NULL) ? "" : $trips[$i]['actual_distance'];               
            

            }

            
            if($trips[$i]['actual_dstime'] != NULL && $trips[$i]['actual_pstime'] != NULL)
            {
            
            $secs1 = strtotime($trips[$i]['actual_dstime'])-strtotime("00:00:00");
            $totalservicetime = date("H:i:s",strtotime($trips[$i]['actual_pstime'])+$secs1);

            }
            else if ($trips[$i]['actual_pstime'] == NULL){
                $totalservicetime=$trips[$i]['actual_dstime'];

            }
            else if($trips[$i]['actual_dstime'] == NULL)
            {

                $totalservicetime=$trips[$i]['actual_pstime'];

            }
            else{
                $totalservicetime='00:00:00';
            }

            if($trips[$i]['pickup_service_time'] != NULL && $trips[$i]['dropoff_service_time'] != NULL)
            {
            $secs1 = strtotime($trips[$i]['pickup_service_time'])-strtotime("00:00:00");
            $plannedtotalservicetime = date("H:i:s",strtotime($trips[$i]['dropoff_service_time'])+$secs1);

            }
            else if ($trips[$i]['dropoff_service_time'] == NULL){

                $plannedtotalservicetime=$trips[$i]['pickup_service_time'];

            }
            else if($trips[$i]['pickup_service_time'] == NULL)
            {

                $plannedtotalservicetime=$trips[$i]['dropoff_service_time'];

            }
            else{
                $plannedtotalservicetime='00:00:00';
            }
            $category_key = $trips[$i]['order']['category']['key'];


            if(Auth::guard('oms')->check() && $category_key == "PICKUP"){
                        
                $diff_in_minutes = \Carbon\Carbon::parse($trips[$i]['trip_startime'])->diffInMinutes(\Carbon\Carbon::parse( $trips[$i]['load']));
                $hours = floor($diff_in_minutes / 60);
                $minutes = $diff_in_minutes % 60;
                
                $trip_Time = "".str_pad($hours, 2, '0', STR_PAD_LEFT). ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
                $trip_dist = _getDistance($trips[$i]['start_latitude'],$trips[$i]['start_longitude'],$trips[$i]['pickup_latitude'],$trips[$i]['pickup_longitude']);
                $totalservicetime = $trips[$i]['actual_pstime'];
                $plannedtotalservicetime = $trips[$i]['pickup_service_time'];

                $estimated_time=date_format(date_create($trips[$i]['pickup_time']), "Y-m-d");
  
                $estimated_dist=$trips[$i]['pickup_distance'];

                if($trips[$i]['load'] != null)
                {
                    $trip_status = TripStatus::where('key', 'CLOSED')->value('trip_status_title');

                    if (isset($trip_status)) {
                        $trip_status=json_decode($trip_status, true);
                        //echo print_r($trip_status);
                    }
                    //$trip_status='{"en":"Closed","ar":"مغلق"}';
                }
                else {
                    $trip_status=json_decode($trips[$i]['trip_status']['trip_status_title'], true);
                }

            }
            else{
                $trip_status=json_decode($trips[$i]['trip_status']['trip_status_title'], true);
                $estimated_time=($trips[$i]['total_time'] == NULL) ? "" : $trips[$i]['total_time'];
                $estimated_dist= ($trips[$i]['total_distance'] == NULL) ? "" : $trips[$i]['total_distance'].' KM';


            }

            $vehicles[] = [
                'delivery_trip_id' => $trips[$i]['delivery_trip_id'],
                'order_number' => isset($trips[$i]['order']) ? $trips[$i]['order']['order_number'] : '',
                'trip_code' => $trips[$i]['trip_code'],
                'trip_type' => $trips[$i]['delivery_trip_type'],
                'trip_date' => date_format($trip_date, "Y-m-d"),
                'created_at' => $trips[$i]['created_at'],//$created_date,
                'trip_startime' => $trips[$i]['trip_startime'],//$trip_startime,
                'pickup_check_in' => $trips[$i]['pickup_check_in'],
                'pickup' => $trips[$i]['load'],
                'dropoff_check_in' => $trips[$i]['dropoff_check_in'],
                'dropoff' => $trips[$i]['unload'],
                'closed' => $trips[$i]['trip_endtime'],
                'trip_endtime' => $trips[$i]['trip_endtime'],//$trip_endtime,
            
               
                'trip_status' => $trip_status,
                'trip_status_key' => $trips[$i]['trip_status']['key'],
               
                'estimated_time' => $estimated_time,
                'estimated_dist' => $estimated_dist,
                'calculated_time' => $trip_Time,
                'calculated_dist' => ($trip_dist == NULL) ? "" : $trip_dist.' KM',
                'is_cancellable' => ($trips[$i]['trip_endtime'] == NULL) ? "True" : "False",

                'pickup_service_time' => ($trips[$i]['actual_pstime'] == NULL) ? "" : $trips[$i]['actual_pstime'],
     
                'dropoff_service_time' => ($trips[$i]['actual_dstime'] == NULL) ? "" : $trips[$i]['actual_dstime'],

                'planned_pickup_service_time' => ($trips[$i]['pickup_service_time'] == NULL) ? "" : $trips[$i]['pickup_service_time'],

                'planned_dropoff_service_time' => ($trips[$i]['dropoff_service_time'] == NULL) ? "" : $trips[$i]['dropoff_service_time'],
                "planned_total_service_time" => ($plannedtotalservicetime == NULL || $plannedtotalservicetime == 0)? "":$plannedtotalservicetime,
                "total_service_time" => ($totalservicetime == NULL)? "":$totalservicetime,
                'is_editable' => ($trips[$i]['trip_startime'] == NULL) ? "True" : "False",
                'vehicle_id' => $trips[$i]['vehicle']['vehicle_id'],

                'start_latitude' => ($trips[$i]['start_latitude'] == NULL) ? "" : $trips[$i]['start_latitude'],
                'start_longitude' => ($trips[$i]['start_longitude'] == NULL) ? "" : $trips[$i]['start_longitude'],
                'pickup_latitude' => ($trips[$i]['pickup_latitude'] == NULL) ? "" : $trips[$i]['pickup_latitude'],
                'pickup_longitude' => ($trips[$i]['pickup_longitude'] == NULL) ? "" : $trips[$i]['pickup_longitude'],
                'dropoff_latitude' => ($trips[$i]['dropoff_latitude'] == NULL) ? "" : $trips[$i]['dropoff_latitude'],
                'dropoff_longitude' => ($trips[$i]['dropoff_longitude'] == NULL) ? "" : $trips[$i]['dropoff_longitude'],

                'start_latitude' => ($trips[$i]['start_latitude'] == NULL) ? "" : $trips[$i]['start_latitude'],

              
                'vehicle_type_id' => $trips[$i]['vehicle']['vehicle_type_id'],
                'vehicle_type' => isset($trips[$i]['vehicle']['vehicle_type']['vehicle_type']) ? json_decode($trips[$i]['vehicle']['vehicle_type']['vehicle_type'], true) : "",
                'vehicle_type_key' => $trips[$i]['vehicle']['vehicle_type']['key'],
                'vehicle_category_id' => $trips[$i]['vehicle']['vehicle_category_id'],
                'vehicle_category' => isset($trips[$i]['vehicle']['vehicle_category']['vehicle_category']) ? json_decode($trips[$i]['vehicle']['vehicle_category']['vehicle_category'], true) : "",
                'vehicle_category_key' => isset($trips[$i]['vehicle']['vehicle_category']['key']) ? $trips[$i]['vehicle']['vehicle_category']['key'] : "",
                'vehicle_opening_odometer' => ($trips[$i]['vehicle']['vehicle_opening_odometer'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_opening_odometer'],
                'vehicle_code' => ($trips[$i]['vehicle']['vehicle_code'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_code'],

                'speed' => (json_decode($trips[$i]['vehicle']['speed'], true) == NULL) ? "" : json_decode($trips[$i]['vehicle']['speed'], true),
                'vehicle_plate_number' => $trips[$i]['vehicle']['vehicle_plate_number'],


                'driver' => !isset($trips[$i]['vehicle']['user']) ? null : [
                    'user_id' => $trips[$i]['vehicle']['user']['user_id'],
                    'name' => $temp_name,
                    'phone' => $trips[$i]['vehicle']['user']['phone'],
                    'salary' => $trips[$i]['vehicle']['user']['salary'],
                  
                ],
            ];
            
           
        }

        if(isset($parameterArray['offset'])|| $parameterArray['offset'] != 0)
        {
            $offset = $parameterArray['offset'];
            $vehicles = paginate($vehicles,$offset);
       
        }

       
        if (count($vehicles) > 0) {
            return response()->json([
                "code" => 200,
                "data" => $vehicles,
                "message" => __("Trips Loaded!")
            ]);
        } else {
            return response()->json([
                "code" => 203,
                "data" => $vehicles,
                "message" => __("No Trips Loaded!")
            ]);
        }

    
    }

    function getTripListingDetailedAction(Request $request, $store_id)
    {
        $parameterArray1 = $request->all();
      
        $parameterArray =  json_decode($request->get("data"), true);
      
        if ($parameterArray != NULL || $parameterArray != null) {
     

            $validator = Validator::make([
                'fdate' => @$parameterArray['startDate'],
                'todate' => @$parameterArray['endDate'],
                'store_id' => $store_id,
                'status' => @$parameterArray['status'],
                'driver' => @$parameterArray['driver'],
                'trip_type' => @$parameterArray['tripType'],
                'vehicle' => @$parameterArray['vehicle'],
                'vehicle_category' => @$parameterArray['vehicleCategory'],
                'vehicle_type' => @$parameterArray['vehicleType'],
                'address_id' => @$parameterArray['address_id'],
            ], [
                'fdate' => 'date|date_format:Y-m-d|nullable',
                'todate' => 'date|date_format:Y-m-d|nullable',
                'store_id' => 'required|int|min:1',
                'status' => 'nullable|string',
                'driver' => 'nullable|int',
                'trip_type' => ['nullable', Rule::in(['Custom', 'Dynamic', 'Static'])],
                'vehicle' => 'nullable|int',
                'vehicle_category' => 'nullable|string',
                'vehicle_type' => 'nullable|string',
                'batch_no' => 'nullable|string|min:1',
                'customer_id' => 'nullable|string|min:1',
                'address_id' => 'nullable|string|min:1'
            ]);
        } elseif ($parameterArray == NULL || $parameterArray == null) {
            return response()->json([
                'status' => 'error',
                'code' => '300',
                'message' => 'Query Parameters Missing!'
            ]);
        } else {
            $validator = Validator::make(['sal_off_id' => $store_id], [

                'store_id' => 'required|int|min:1'
            ]);
        }

        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        try {
            Store::where('store_id', $store_id);
        } catch (\Exception $ex) {
            return response()->json([
                "code" => 500,
                "message" => "Couldn't find warehouse with your given id"
            ]);
        }
        $deliverytripids = DeliveryTrip::distinct('delivery_trip_id')->select('delivery_trip_id')->get()->toArray();  

        // $assigned_to_cust = [];
        if(!Auth::guard('oms')->check()){
            $user = (Auth::user());
            $user_id = ($user->user_id);  
            // $assigned_to_cust = Customer::where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id)->orWhere('dispatcher_id',$user_id)->get()->toArray();

        }        

        $trips = DeliveryTrip::with([
            'trip_status',
            'vehicle.user',
            'vehicle.vehicle_type',
            'vehicle.vehicle_category',
            'order',
            'order.category',
            'order.customer',
            'order.address',
            'order.site_location',
            'order.shipping_address',

        ])->whereIn('delivery_trip_id', $deliverytripids);
        // if(count($assigned_to_cust) > 0){
        //     if(!Auth::guard('oms')->check()){
        //     $trips->whereHas('order.customer', function($query) use($user_id){
        //       $query->where('estimator_id',$user_id)->orWhere('project_manager_id',$user_id)->orWhere('dispatcher_id',$user_id);
        //     });
        // }
        // }
        
        
        if ($parameterArray != null || $parameterArray != NULL) {
            if (isset($parameterArray['status']) && ($parameterArray['status'] != '' || $parameterArray['status'] != NULL || $parameterArray['status'] != null)) {

                $tripStatus = $parameterArray['status'];

                $trips = $trips->whereHas('trip_status', function ($query) use ($tripStatus) {
                    $query->where('key', $tripStatus);
                });
            }
            if (isset($parameterArray['order_number']) && ($parameterArray['order_number'] != '' || $parameterArray['order_number'] != NULL || $parameterArray['order_number'] != null)) {

                $order_number = $parameterArray['order_number'];

                $trips = $trips->whereHas('order', function ($query) use ($order_number) {
                    $query->where('order_number', $order_number);
                });
            }

            if (isset($parameterArray['customer_id']) && ($parameterArray['customer_id'] != '' || $parameterArray['customer_id'] != NULL || $parameterArray['customer_id'] != null)) {

                $customer_id = $parameterArray['customer_id'];

                $trips = $trips->whereHas('order.customer', function ($query) use ($customer_id) {
                    $query->where('customer_id', $customer_id);
                });
            }

            if (isset($parameterArray['approveDD']) && ($parameterArray['approveDD'] != '' || $parameterArray['approveDD'] != NULL || $parameterArray['approveDD'] != null)) {


                if ($parameterArray['approveDD'] == 'false') {

                    $tripApprove = $parameterArray['approveDD'];
                    $converted_res = $tripApprove ? 'false' : 'true';

                    $trips = $trips->where('is_approved', $converted_res);
                } else if ($parameterArray['approveDD'] == 'true') { {

                        $tripApprove = $parameterArray['approveDD'];
                        $converted_res = $tripApprove ? 'true' : 'false';


                        $trips = $trips->where('is_approved', $converted_res);
                    }
                }
            }

            if (isset($parameterArray['tripType']) && ($parameterArray['tripType'] != '' || $parameterArray['tripType'] != NULL || $parameterArray['tripType'] != null)) {

                $tripType = $parameterArray['tripType'];
                $trips = $trips->where('delivery_trip_type', $tripType);
            }


            //vehicel_category_key
            if (isset($parameterArray['vehicleCategory']) && ($parameterArray['vehicleCategory'] != '' || $parameterArray['vehicleCategory'] != NULL || $parameterArray['vehicleCategory'] != null)) {

                $vehicleCategory = $parameterArray['vehicleCategory'];
                $trips = $trips->whereHas('vehicle.vehicle_category', function ($query) use ($vehicleCategory) {
                    $query->where('key', $vehicleCategory);
                });
            }

            //vehicle_type_key
            if (isset($parameterArray['vehicleType']) && ($parameterArray['vehicleType'] != '' || $parameterArray['vehicleType'] != NULL || $parameterArray['vehicleType'] != null)) {

                $vehicleType = $parameterArray['vehicleType'];
                $trips = $trips->whereHas('vehicle.vehicle_type', function ($query) use ($vehicleType) {
                    $query->where('key', $vehicleType);
                });
            }
            //drive_id
            if (isset($parameterArray['driver']) && ($parameterArray['driver'] != '' || $parameterArray['driver'] != NULL || $parameterArray['driver'] != null)) {

                $vehicleDriver = $parameterArray['driver'];

                $trips = $trips->whereHas('vehicle.driver', function ($query) use ($vehicleDriver) {
                    $query->where('user_id', $vehicleDriver);
                });
            }
            //vehicle_id
            if (isset($parameterArray['vehicle']) && ($parameterArray['vehicle'] != '' || $parameterArray['vehicle'] != NULL || $parameterArray['vehicle'] != null)) {

                $vehicle = $parameterArray['vehicle'];
                $trips = $trips->whereHas('vehicle', function ($query) use ($vehicle) {
                    $query->where('vehicle_id', $vehicle);
                });
            }

            //batch_number
            if (isset($parameterArray['batch_no']) && ($parameterArray['batch_no'] != '' || $parameterArray['batch_no'] != NULL || $parameterArray['batch_no'] != null)) {

                $batchNo = $parameterArray['batch_no'];
                $trips = $trips->where('batch_no', $batchNo);
            }
            if (isset($parameterArray['delivery_trip_id']) && ($parameterArray['delivery_trip_id'] != '' || $parameterArray['delivery_trip_id'] != NULL || $parameterArray['delivery_trip_id'] != null)) {

                $deliverytripid = $parameterArray['delivery_trip_id'];

                $trips = $trips->where('delivery_trip_id', $deliverytripid);
            }


            //trip_date
            if (isset($parameterArray['startDate']) && ($parameterArray['startDate'] != '' || $parameterArray['startDate'] != NULL || $parameterArray['startDate'] != null) && isset($parameterArray['endDate']) && ($parameterArray['endDate'] != '' || $parameterArray['endDate'] != NULL || $parameterArray['endDate'] != null)) {

                $from = $parameterArray['startDate'];
                $to = $parameterArray['endDate'];
                $trips = $trips->whereBetween(\DB::raw('DATE(trip_date)'), array($from, $to));
            }

            //customer
            if (isset($parameterArray['customer_id']) && ($parameterArray['customer_id'] != '' || $parameterArray['customer_id'] != NULL || $parameterArray['customer_id'] != null)) {
                $customer_id = $parameterArray['customer_id'];

                $trips = $trips->whereHas('order', function ($query) use ($customer_id) {
                    $query->where('customer_id', $customer_id);
                });
            }

            //address, pickup, site location, shipping address
            if (isset($parameterArray['address_id']) && ($parameterArray['address_id'] != '' || $parameterArray['address_id'] != NULL || $parameterArray['address_id'] != null)) {
                $address_id = $parameterArray['address_id'];

                $trips = $trips->whereHas('order', function($query) use ($address_id) {
                    $query->where('pickup_address_id', $address_id)->orWhere('shipping_address_id', $address_id)->orWhere('site_location', $address_id);
                });
            }
        }

        $trips = $trips->where('status', 1)->orderBy('delivery_trip_id', 'DESC')->get()->toArray();
       
        $vehicles = [];
        $trip_ids = [];
   
        for ($i = 0, $count = count($trips); $i < $count; $i++) {
        
            $trip_date = date_create($trips[$i]['trip_date']);
            $created_date = ($trips[$i]['created_at'] == NULL) ? "" : date_format(date_create($trips[$i]['created_at']), "Y-m-d H:i:s");
            $trip_startime = ($trips[$i]['trip_startime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_startime']), "Y-m-d H:i:s");
            $trip_endtime = ($trips[$i]['trip_endtime'] == NULL) ? "" : date_format(date_create($trips[$i]['trip_endtime']), "Y-m-d H:i:s");
            $temp_name = "";

            //For displaying trips_pickup location
            if($trips[$i]['customer_pickup_loc_id'] != null ){
                $pickup_address = \App\Model\Address::where('address_id',$trips[$i]['customer_pickup_loc_id'])->value('address_title');
            }
            elseif($trips[$i]['aqg_pickup_loc_id'] != null ){
                $pickup_address = \App\Model\Store::where('store_id',$trips[$i]['aqg_pickup_loc_id'])->value('store_name');
            }else{
                // $pickup_address = Order::with('address')->where('order_id',$trips[$i]['order_id'])->pluck(['address.address_title']);
                $pickup_address = Order::select('addresses.address_title')->join('addresses', 'orders.pickup_address_id', '=', 'addresses.address_id')
                                  ->where('order_id',$trips[$i]['order_id'])->value('addresses.address_title');
            }
           
            //For displaying trips_dropoff location
            if($trips[$i]['customer_dropoff_loc_id'] != null ){
                $dropoff_address = \App\Model\Address::where('address_id',$trips[$i]['customer_dropoff_loc_id'])->value('address_title');
            }
            elseif($trips[$i]['aqg_dropoff_loc_id'] != null ){
                $dropoff_address = \App\Model\Store::where('store_id',$trips[$i]['aqg_dropoff_loc_id'])->value('store_name');
            }else{
                $dropoff_address_id = Order::where('order_id',$trips[$i]['order_id'])->value('aqg_dropoff_loc_id');
                if(isset($dropoff_address_id) && $dropoff_address_id != null){
                    $dropoff_address = \App\Model\Store::where('store_id',$dropoff_address_id)->value('store_name');
                }else{
                    $dropoff_address_id = Order::where('order_id',$trips[$i]['order_id'])->value('customer_dropoff_loc_id');
                    if(isset($dropoff_address_id) && $dropoff_address_id != null){
                        $dropoff_address = \App\Model\Address::where('address_id',$dropoff_address_id)->value('address_title');
                    }
                }
                
               
            }
           

            if (isset($trips[$i]['vehicle']['user'])) {
                $userid = $trips[$i]['vehicle']['user']['user_id'];

                $username = $trips[$i]['vehicle']['user'];

                if (isset($username['first_name']) || isset($username['last_name'])) {


                    $username['first_name'] = isset($username['first_name']) && $username['first_name'] != null ? $username['first_name'] : null;
                    $username['last_name'] = isset($username['last_name']) && $username['last_name'] != null ? $username['last_name'] : null;
                    $temp_name = '{"en":"' . $username['first_name'] . ' ' . $username['last_name'] . '","ar":"' . $username['first_name'] .
                        ' ' . $username['last_name'] . '"}';
                    $temp_name = json_decode($temp_name);
                } else {
                    $temp_name = $username['name'];
                }
            }
           
            if ($trips[$i]['trip_status']['key'] != "CLOSED" && $trips[$i]['trip_status']['key'] != "ASSIGNED" && $trips[$i]['trip_status']['key'] != "CANCEL")
            {
                $mytime = Carbon::now();


               
                $starttime=$trip_startime;
                $starttime = Carbon::createFromFormat('Y-m-d H:i:s', $starttime);
                
               
                $diff = $starttime->diffInMinutes($mytime);
                //$trip_Time = gmdate("H:i:s", ($diff * 60));
                $hours = floor($diff / 60);
                $minutes = $diff % 60;
                    
                $trip_Time = "".str_pad($hours, 2, '0', STR_PAD_LEFT). ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
               
                $getLoc=DB::table('vehicle_locations')->where('delivery_trip_id',$trips[$i]['delivery_trip_id'])
                ->pluck('vehLoc')->toArray();
               
                if(is_array($getLoc) && count($getLoc) > 0)
                {
                    $all_data = json_decode($getLoc[0]);
                    if(!is_array($all_data)){
                        return response()->json([
                            "code" => 403,
                            "data" => "",
                            "message" => __($trips[$i]['delivery_trip_id']." trip has wrong vehicle locations stored")
                        ]);
                    }
                    $index=count($all_data);
                    $index=$index-1;
                    $all_data= $all_data[$index];
            
                    $lat2=$all_data->lat;
                    $lng2=$all_data->lng;
                    $lat=($trips[$i]['start_latitude'] == NULL) ? "" : $trips[$i]['start_latitude'];
                    $lng=($trips[$i]['start_longitude'] == NULL) ? "" : $trips[$i]['start_longitude'];
                
                    if($lat == NULL||$lng == NULL || $lat2 == NULL || $lng2 == NULL )
                    {
                        $trip_dist= 0 . " KM";
                    }
                    else {
                        $trip_dist= _getDistance($lat,$lng,$lat2,$lng2);
                        $trip_dist= $trip_dist ." KM";

                    }
                }
                else {
                    $trip_dist=0 ." KM";

                }
           
            }
            else if($trips[$i]['trip_status']['key'] == "CLOSED")
            {
               
               $trip_Time = $trips[$i]['actual_time'] == NULL ? "" : $trips[$i]['actual_time'];
               $trip_dist=($trips[$i]['actual_distance'] == NULL) ? 0 : $trips[$i]['actual_distance']." KM"; 
            }
            else {
                $trip_Time =  $trips[$i]['actual_time'];
                $trip_dist=$trips[$i]['actual_distance']; 
            }
            
            if ($trips[$i]['actual_dstime'] != NULL && $trips[$i]['actual_pstime'] != NULL)
            {
            
            $secs1 = strtotime($trips[$i]['actual_dstime'])-strtotime("00:00:00");
            $totalservicetime = date("H:i:s",strtotime($trips[$i]['actual_pstime'])+$secs1);

            }
            else if ($trips[$i]['actual_pstime'] == NULL){
                $totalservicetime=$trips[$i]['actual_dstime'];

            }
            else if($trips[$i]['actual_dstime'] == NULL)
            {

                $totalservicetime=$trips[$i]['actual_pstime'];

            }
            else{
                $totalservicetime='00:00:00';
            }

            if($trips[$i]['pickup_service_time'] != NULL && $trips[$i]['dropoff_service_time'] != NULL)
            {
            $secs1 = strtotime($trips[$i]['pickup_service_time'])-strtotime("00:00:00");
            $plannedtotalservicetime = date("H:i:s",strtotime($trips[$i]['dropoff_service_time'])+$secs1);

            }
            else if ($trips[$i]['dropoff_service_time'] == NULL){

                $plannedtotalservicetime=$trips[$i]['pickup_service_time'];

            }
            else if($trips[$i]['pickup_service_time'] == NULL)
            {

                $plannedtotalservicetime=$trips[$i]['dropoff_service_time'];

            }
            else{
                $plannedtotalservicetime='00:00:00';
            }
            
            $category_key = $trips[$i]['order']['category']['key'];
            


            if(Auth::guard('oms')->check() && $category_key == "PICKUP"){
                        
                $diff_in_minutes = \Carbon\Carbon::parse($trips[$i]['trip_startime'])->diffInMinutes(\Carbon\Carbon::parse( $trips[$i]['load']));
                $hours = floor($diff_in_minutes / 60);
                $minutes = $diff_in_minutes % 60;
                
                $trip_Time = "".str_pad($hours, 2, '0', STR_PAD_LEFT). ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
                $trip_dist = _getDistance($trips[$i]['start_latitude'],$trips[$i]['start_longitude'],$trips[$i]['pickup_latitude'],$trips[$i]['pickup_longitude']);
                $trip_dist=$trip_dist.' KM';
                $totalservicetime = $trips[$i]['actual_pstime'];
                $plannedtotalservicetime = $trips[$i]['pickup_service_time'];

                $estimated_time=date_format(date_create($trips[$i]['pickup_time']), "H:i:s");
  
                $estimated_dist=$trips[$i]['pickup_distance'].' KM';

                if($trips[$i]['load'] != null)
                {
                    $trip_status = TripStatus::where('key', 'CLOSED')->value('trip_status_title');

                    if (isset($trip_status)) {
                        $trip_status=json_decode($trip_status, true);
                        //echo print_r($trip_status);
                    }
                    //$trip_status='{"en":"Closed","ar":"مغلق"}';
                }
                else {
                    $trip_status=json_decode($trips[$i]['trip_status']['trip_status_title'], true);
                }

            }
            else{
                $trip_status=json_decode($trips[$i]['trip_status']['trip_status_title'], true);
                $estimated_time=($trips[$i]['total_time'] == NULL) ? "" : $trips[$i]['total_time'];
                $estimated_dist= ($trips[$i]['total_distance'] == NULL) ? "" : $trips[$i]['total_distance'].' KM';


            }
            $vehicles[] = [
                'delivery_trip_id' => $trips[$i]['delivery_trip_id'],
                'order_number' => isset($trips[$i]['order']) ? $trips[$i]['order']['order_number'] : '',
                'customer_id' => isset($trips[$i]['order']) && isset($trips[$i]['order']['customer']) && isset($trips[$i]['order']['customer']['customer_id']) && $trips[$i]['order']['customer']['customer_id'] != null ? $trips[$i]['order']['customer']['customer_id'] : null,
                'customer_name' => isset($trips[$i]['order']) && isset($trips[$i]['order']['customer']) && isset($trips[$i]['order']['customer']['name']) && $trips[$i]['order']['customer']['name'] != null ? $trips[$i]['order']['customer']['name'] : null,
                'pickup_location' => isset($pickup_address) && isset($pickup_address) && $pickup_address != null ? $pickup_address : null,
                'dropoff_location' => isset($dropoff_address) && isset($dropoff_address) && $dropoff_address != null ? $dropoff_address : null,
                'trip_code' => $trips[$i]['trip_code'],
                'trip_type' => $trips[$i]['delivery_trip_type'],
                'trip_date' => date_format($trip_date, "Y-m-d"),
                'created_at' => $trips[$i]['created_at'],//$created_date,
                'trip_startime' => $trips[$i]['trip_startime'],//$trip_startime,
                'pickup_check_in' => $trips[$i]['pickup_check_in'],
                'pickup' => $trips[$i]['load'],
                'dropoff_check_in' => $trips[$i]['dropoff_check_in'],
                'dropoff' => $trips[$i]['unload'],
                'closed' => $trips[$i]['trip_endtime'],
                'trip_endtime' => $trips[$i]['trip_endtime'],//$trip_endtime,
            
               
                'trip_status' => $trip_status,
                'trip_status_key' => $trips[$i]['trip_status']['key'],

                'estimated_time' => $estimated_time,
                'estimated_dist' => $estimated_dist,
                'calculated_time' => $trip_Time,
                'calculated_dist' => $trip_dist,
                'is_cancellable' => ($trips[$i]['trip_endtime'] == NULL) && $trips[$i]['trip_status']['key'] != "CANCEL" && $trips[$i]['trip_status']['key'] != "CANCELLED" ? "True" : "False",
              
                'pickup_service_time' => ($trips[$i]['actual_pstime'] == NULL) ? "" : $trips[$i]['actual_pstime'],
     
                'dropoff_service_time' => ($trips[$i]['actual_dstime'] == NULL) ? "" : $trips[$i]['actual_dstime'],

                'planned_pickup_service_time' => ($trips[$i]['pickup_service_time'] == NULL) ? "" : $trips[$i]['pickup_service_time'],

                'planned_dropoff_service_time' => ($trips[$i]['dropoff_service_time'] == NULL) ? "" : $trips[$i]['dropoff_service_time'],
                "planned_total_service_time" => ($plannedtotalservicetime == NULL || $plannedtotalservicetime == 0)? "":$plannedtotalservicetime,


                "total_service_time" => ($totalservicetime == NULL)? "":$totalservicetime,

                'vehicle_id' => isset($trips[$i]['vehicle']) && isset($trips[$i]['vehicle']['vehicle_id']) && $trips[$i]['vehicle']['vehicle_id'] != null ? $trips[$i]['vehicle']['vehicle_id'] : null,

                'start_latitude' => ($trips[$i]['start_latitude'] == NULL) ? "" : $trips[$i]['start_latitude'],
                'start_longitude' => ($trips[$i]['start_longitude'] == NULL) ? "" : $trips[$i]['start_longitude'],
                'pickup_latitude' => ($trips[$i]['pickup_latitude'] == NULL) ? "" : $trips[$i]['pickup_latitude'],
                'pickup_longitude' => ($trips[$i]['pickup_longitude'] == NULL) ? "" : $trips[$i]['pickup_longitude'],
                'dropoff_latitude' => ($trips[$i]['dropoff_latitude'] == NULL) ? "" : $trips[$i]['dropoff_latitude'],
                'dropoff_longitude' => ($trips[$i]['dropoff_longitude'] == NULL) ? "" : $trips[$i]['dropoff_longitude'],

                'start_latitude' => ($trips[$i]['start_latitude'] == NULL) ? "" : $trips[$i]['start_latitude'],
              
                'vehicle_type_id' => isset($trips[$i]['vehicle']) && isset($trips[$i]['vehicle']['vehicle_type_id']) && $trips[$i]['vehicle']['vehicle_type_id'] != null ? $trips[$i]['vehicle']['vehicle_type_id'] : null,
                'vehicle_type' => isset($trips[$i]['vehicle']['vehicle_type']['vehicle_type']) ? json_decode($trips[$i]['vehicle']['vehicle_type']['vehicle_type'], true) : "",
                'vehicle_type_key' => isset($trips[$i]['vehicle']) && isset($trips[$i]['vehicle']['vehicle_type']) && isset($trips[$i]['vehicle']['vehicle_type']['key']) && $trips[$i]['vehicle']['vehicle_type']['key'] != null ? $trips[$i]['vehicle']['vehicle_type_id'] : null,
                
                'vehicle_category_id' => isset($trips[$i]['vehicle']) && isset($trips[$i]['vehicle']['vehicle_category_id']) ? $trips[$i]['vehicle']['vehicle_category_id'] : null ,
                'vehicle_category' => isset($trips[$i]['vehicle']['vehicle_category']['vehicle_category']) ? json_decode($trips[$i]['vehicle']['vehicle_category']['vehicle_category'], true) : "",
                'vehicle_category_key' => isset($trips[$i]['vehicle']['vehicle_category']['key']) ? $trips[$i]['vehicle']['vehicle_category']['key'] : "",
                'vehicle_opening_odometer' => !isset($trips[$i]['vehicle']) || ($trips[$i]['vehicle']['vehicle_opening_odometer'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_opening_odometer'],
                'vehicle_code' => !isset($trips[$i]['vehicle']) || ($trips[$i]['vehicle']['vehicle_code'] == NULL) ? "" : $trips[$i]['vehicle']['vehicle_code'],

                'speed' => !isset($trips[$i]['vehicle']) || (json_decode($trips[$i]['vehicle']['speed'], true) == NULL) ? "" : json_decode($trips[$i]['vehicle']['speed'], true),
                'vehicle_plate_number' => isset($trips[$i]['vehicle']) && isset($trips[$i]['vehicle']['vehicle_plate_number']) && $trips[$i]['vehicle']['vehicle_plate_number'] != null ? $trips[$i]['vehicle']['vehicle_plate_number'] : null,


                'driver' => !isset($trips[$i]['vehicle']['user']) ? null : [
                    'user_id' => $trips[$i]['vehicle']['user']['user_id'],
                    'name' => $temp_name,
                    'phone' => $trips[$i]['vehicle']['user']['phone'],
                    'salary' => $trips[$i]['vehicle']['user']['salary'],
                  
                ],
            ];
           
        }
        if(isset($parameterArray['pagination']) && ($parameterArray['pagination'] == 1 || $parameterArray['pagination'] == true))
        {
           $parameterArray1['perPage'] = isset($parameterArray1['perPage']) && $parameterArray1['perPage'] != '' ?$parameterArray1['perPage'] : 10;
            $offset = $parameterArray['pagination'];
          
            $vehicles = custom_paginate($vehicles, $parameterArray1['perPage']);


        }
             
        if (count($vehicles) > 0) {
            return response()->json([
                "code" => 200,
                "data" => ($vehicles),
                "message" => __("Trips Loaded!")
            ]);
        } else {
            return response()->json([
                "code" => 203,
                "data" => ($vehicles),
                "message" => __("No Trips Loaded!")
            ]);
        }
    }

    function getTripDeliveriesListingAction(Request $request, $store_id, $delivery_trip_id)
    {
        $validatedData = \Validator::make(['trip_id' => $delivery_trip_id], [
            'trip_id' => 'required|int|min:1',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                "code" => 500,
                "message" => "Trip id is inValid!"
            ]);
        }

        try {
            DeliveryTrip::findOrFail($delivery_trip_id);
        } catch (\Exception $ex) {
            return response()->json([
                "code" => 500,
                "message" => "Couldn't find trip with your given id!"
            ]);
        }

        $trip = DeliveryTrip::with([
            'pickup_material.material',
            'dropoff_material.material',
            'pickup_material.pickup_unit',
            'dropoff_material.dropoff_unit',
            'trip_status',
            'vehicle',
            'constraints.locations',
            'vehicle.user',
            'order.address',
            'order.address.location',
            'order.order_status',
            'order.customer',
            'store.address',
            'order.address.location',
            'order.order_material.material',
            'order.order_material.skips.asset_inventory:asset_id,title',
            'order.order_material.material_unit',
            'trip_status'
        ])->where('delivery_trip_id', $delivery_trip_id)->where('store_id', $store_id)->get()->toArray();


        if (count($trip) == 0) {

            return response()->json([
                "code" => 200,
                "data" => [],
                "message" => __("No Records Loaded!"),
            ]);
        }
       
        $deliveries = $trip;
        $isCancelable = true;


        $delivery_data = [];
        for ($i = 0, $count = count($deliveries); $i < $count; $i++) {
            for ($j = 0, $counter = count($deliveries[$i]['order']); $j < $counter; $j++) {
                $service_time = "";

                if ($trip[0]['trip_startime'] != null || $trip[0]['trip_startime'] != NULL) {

                    try {
                        $results = DeliveryTrip::with(['order', 'vehicle',])
                            ->where('order_id', (string) $deliveries[$i]['order']['order_id'])->get();

                        if (!$results->isEmpty()) {

                            $result = json_decode($results, true);

                            $start_time = 0;
                            $end_time = 0;

                            foreach ($result as $key => $value) {
                                if ($value['pickup_check_in'] != null || $value['pickup_check_in'] != NULL) {
                                    $resultKpi[0] = array(

                                        'driver' => $value['vehicle']['driver_id'],
                                        'pickup_check_in' => $value['pickup_check_in'],
                                        'description' => $value['pickup_check_in'],
                                    );
                                } else {
                                    $resultKpi[0] = array(


                                        'description' => 'Driver has not checked in yet for load pickup',
                                    );
                                }

                                if ($value['load'] != null || $value['load'] != NULL) {

                                    $resultKpi[1] = array(

                                        'load' => $value['load'],
                                        'description' => $value['load'],
                                    );
                                } else {
                                    $resultKpi[1] = array(


                                        'description' => 'Driver has not loaded material yet',
                                    );
                                }
                                if ($value['dropoff_check_in'] != null || $value['dropoff_check_in'] != NULL) {
                                    $resultKpi[2] = array(

                                        'delivered' => $value['dropoff_check_in'],
                                        'description' => $value['dropoff_check_in'],
                                    );
                                } else {
                                    $resultKpi[2] = array(
                                        'description' => 'Driver has not checked in yet for load dropoff',
                                    );
                                }

                                if ($value['unload'] != null || $value['unload'] != NULL) {
                                    $resultKpi[3] = array(

                                        'delivered' => $value['unload'],
                                        'description' => $value['unload'],
                                    );
                                } else {
                                    $resultKpi[3] = array(
                                        'description' => 'Driver has not unloaded material yet',
                                    );
                                }

                                if ($value['trip_endtime'] != null || $value['trip_endtime'] != NULL) {
                                    $resultKpi[4] = array(

                                        'delivered' => $value['trip_endtime'],
                                        'description' => $value['trip_endtime'],
                                    );
                                } else {
                                    $resultKpi[4] = array(
                                        'description' => 'Driver has not ended trip yet',
                                    );
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $resultKpi = ["error" => $e->getMessage()];
                    }
                } else {
                    $resultKpi = [];
                }

                $kpi = $resultKpi;

                $items[] = [
                    "product_id" => $deliveries[$i]['order'],
                    "product_name" => json_decode(json_encode($deliveries[$i]['order'])),
                    "quantity" => $deliveries[$i]['order'],

                ];
            }

            if ($deliveries[$i]['load'] == null || $deliveries[$i]['load'] == NULL) {
                $actualservicetime = 0;
            } else {
                $datetime1 = new DateTime($deliveries[$i]['pickup_check_in']);
                $datetime2 = new DateTime($deliveries[$i]['load']);

                $hours = $datetime1->diff($datetime2);
                $actualservicetime = (($hours->h) * 60) + ($hours->i);
            }
            

            if (isset($deliveries[$i]['order']['address'])) {
                if ($deliveries[$i]['order']['address']['latitude'] == NULL || $deliveries[$i]['order']['address']['longitude'] == NULL || $deliveries[$i]['order']['address']['latitude'] == 0 || $deliveries[$i]['order']['address']['longitude'] == 0) {
                    if ($deliveries[$i]['order']['address']['latitude'] == 0 || $deliveries[$i]['order']['address']['longitude'] == 0 || $deliveries[$i]['order']['address']['latitude'] == NULL || $deliveries[$i]['order']['address']['longitude'] == NULL) {

                        $lat = isset($deliveries[$i]['order']['address']['location']['latitude']) ? $deliveries[$i]['order']['address']['location']['latitude'] : '';
                        $long = isset($deliveries[$i]['order']['address']['location']['longitude']) ? $deliveries[$i]['order']['address']['location']['latitude'] : '';
                    } else {
                        $lat = $deliveries[$i]['order']['address']['latitude'];
                        $long = $deliveries[$i]['order']['address']['longitude'];
                    }
                } else {
                    $lat = $deliveries[$i]['order']['address']['latitude'];
                    $long = $deliveries[$i]['order']['address']['longitude'];
                }
            } else {
                $lat = 0;
                $long = 0;
            }


            $kpi = $resultKpi;
            if ($deliveries[$i]['actual_distance'] == NULL || $deliveries[$i]['actual_time'] == NULL || $deliveries[$i]['actual_distance'] == null || $deliveries[$i]['actual_time'] == null) {
                $distance = 0;
                $time = 0;
            } else {
                $distance = $deliveries[$i]['actual_distance'];
                $time = $deliveries[$i]['actual_time'];
            }

            $get_Category = DB::table('categories')->where('category_id',$deliveries[$i]['order']['category_id'])
                ->get(['category_id','key'])->first();
            $trip_status=json_decode($trip[0]['trip_status']['trip_status_title'], true);
            

            if ($deliveries[$i]['trip_status']['key'] != "CLOSED" && $deliveries[$i]['trip_status']['key'] != "CANCEL" && $deliveries[$i]['trip_status']['key'] != "ASSIGNED")
            {
                
                if (Auth::guard('oms')->check() && $get_Category && $get_Category->key == "PICKUP") {
                    //calculate distance till pickup_check_in
                    $diff_in_minutes = \Carbon\Carbon::parse($trip[0]['trip_startime'])->diffInMinutes(\Carbon\Carbon::parse( $trip[0]['load']));
                    $hours = floor($diff_in_minutes / 60);
                    $minutes = $diff_in_minutes % 60;
            
                    $time = "".str_pad($hours, 2, '0', STR_PAD_LEFT). ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
                    $distance = _getDistance($trip[0]['start_latitude'],$trip[0]['start_longitude'],$trip[0]['pickup_latitude'],$trip[0]['pickup_longitude']);

                    if($trip[0]['load'] != null)
                    {
                        $trip_status = TripStatus::where('key', 'CLOSED')->value('trip_status_title');

                        if (isset($trip_status)) {
                            $trip_status=json_decode($trip_status, true);
                            //echo print_r($trip_status);
                        }
                    }
                } else {
                    $mytime = Carbon::now();
                    $starttime = $deliveries[0]['trip_startime'];
                    $starttime = Carbon::createFromFormat('Y-m-d H:i:s', $starttime);
                
                    $diff = $starttime->diffInMinutes($mytime);
                    //$time = gmdate("H:i:s", ($diff * 60));
                    $hours = floor($diff / 60);
                    $minutes = $diff % 60;
                        
                    $time = "".str_pad($hours, 2, '0', STR_PAD_LEFT). ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ":00";
                    
                
                    $getLoc=DB::table('vehicle_locations')->where('delivery_trip_id',$deliveries[$i]['delivery_trip_id'])
                    ->pluck('vehLoc')->toArray();
                
                    if(is_array($getLoc) && count($getLoc) > 0)
                    {
                        $all_data = json_decode($getLoc[0]);
                        $index=count($all_data);
                        $index=$index-1;
                        $all_data= $all_data[$index];
                
                        $lat2=$all_data->lat;
                        $lng2=$all_data->lng;
                        $lat=($deliveries[$i]['start_latitude'] == NULL) ? "" : $deliveries[$i]['start_latitude'];
                        $lng=($deliveries[$i]['start_longitude'] == NULL) ? "" : $deliveries[$i]['start_longitude'];
                    
                        if($lat == NULL||$lng == NULL || $lat2 == NULL || $lng2 == NULL )
                        {
                            $distance= 0;
                        }
                        else {
                            $distance= _getDistance($lat,$lng,$lat2,$lng2);
                        }
                    }
                    else {
                        $distance=0;
                    }
                }
            }
            else 
            {
                $time = $deliveries[$i]['actual_time'] == NULL ? "" : $deliveries[$i]['actual_time'];
                $distance=($deliveries[$i]['actual_distance'] == NULL) ? "" : $deliveries[$i]['actual_distance'] ." KM";
            }
 
            $pickup_material = [];

            for ($j = 0; $j < count($deliveries[$i]['pickup_material']); $j++) {
                $pickup['material_id'] = $deliveries[$i]['pickup_material'][$j]['material_id'];
                $pickup['material'] = isset($deliveries[$i]['pickup_material'][$j]['material']) ? $deliveries[$i]['pickup_material'][$j]['material']['name'] : '';
                $pickup['weight'] = $deliveries[$i]['pickup_material'][$j]['weight'];
                $pickup['unit'] = $deliveries[$i]['pickup_material'][$j]['pickup_unit'] != null && $deliveries[$i]['pickup_material'][$j]['pickup_unit']['unit'] != null ? json_decode($deliveries[$i]['pickup_material'][$j]['pickup_unit']['unit']) : null;
                $pickup['e_ticket'] = json_decode($deliveries[$i]['pickup_material'][$j]['e_ticket']);
                $pickup['gate_pass'] = json_decode($deliveries[$i]['pickup_material'][$j]['gate_pass']);

                array_push($pickup_material, $pickup);
            }
          
            $dropoff_material = [];

            for ($j = 0; $j < count($deliveries[$i]['dropoff_material']); $j++) {
                $droppff['material_id'] = $deliveries[$i]['dropoff_material'][$j]['material_id'];
                $droppff['material'] = isset($deliveries[$i]['dropoff_material'][$j]['material']) ? $deliveries[$i]['dropoff_material'][$j]['material']['name'] : '';
                $droppff['weight'] = $deliveries[$i]['dropoff_material'][$j]['weight'];
                $droppff['unit'] = json_decode($deliveries[$i]['dropoff_material'][$j]['dropoff_unit']['unit']);
                $droppff['e_ticket'] = json_decode($deliveries[$i]['dropoff_material'][$j]['e_ticket']);

                array_push($dropoff_material, $droppff);
            }

            $pickconstraints = [];
            $dropconstraints = [];

            for ($j = 0; $j < count($deliveries[$i]['constraints']); $j++) {

                if($deliveries[$i]['constraints'][$j]['location_level_id']==2)
                {
                if($deliveries[$i]['constraints'][$j]['key']=='PICKUP')
                {
                 
                $pickupconstraint['delivery_trip_id'] = $deliveries[$i]['constraints'][$j]['trip_id'];
                $pickupconstraint['location_level_id'] = $deliveries[$i]['constraints'][$j]['location_level_id'] ;
                $pickupconstraint['key'] = $deliveries[$i]['constraints'][$j]['key'];
                $pickupconstraint['delay'] = $deliveries[$i]['constraints'][$j]['delay'];
                $pickupconstraint['name'] = json_decode($deliveries[$i]['constraints'][$j]['locations']['location_name']) ;
                $pickupconstraint['latitude'] = $deliveries[$i]['constraints'][$j]['locations']['latitude'] ;
                $pickupconstraint['longitude'] = $deliveries[$i]['constraints'][$j]['locations']['longitude'] ;

                array_push($pickconstraints, $pickupconstraint);
                }
                else if ($deliveries[$i]['constraints'][$j]['key']=='DROPOFF'){

                    $dropoddconstraint['delivery_trip_id'] = $deliveries[$i]['constraints'][$j]['trip_id'];
                    $dropoddconstraint['location_level_id'] = $deliveries[$i]['constraints'][$j]['location_level_id'] ;
                    $dropoddconstraint['key'] = $deliveries[$i]['constraints'][$j]['key'];
                    $dropoddconstraint['delay'] = $deliveries[$i]['constraints'][$j]['delay'];
                    $dropoddconstraint['name'] = json_decode($deliveries[$i]['constraints'][$j]['locations']['location_name']) ;
                    $dropoddconstraint['latitude'] = $deliveries[$i]['constraints'][$j]['locations']['latitude'] ;
                    $dropoddconstraint['longitude'] = $deliveries[$i]['constraints'][$j]['locations']['longitude'] ;


                array_push($dropconstraints, $dropoddconstraint);
                }
            }
        }
        
        $order_material = [];
        

        if (isset($deliveries[$i]['order'])) {
            for ($j = 0; $j < count($deliveries[$i]['order']['order_material']); $j++) {
                $material['material_id'] = $deliveries[$i]['order']['order_material'][$j]['material_id'];
                $material['material'] = isset($deliveries[$i]['order']['order_material'][$j]['material']) ? $deliveries[$i]['order']['order_material'][$j]['material']['name'] : '';
                $material['weight'] = $deliveries[$i]['order']['order_material'][$j]['weight'];
                $material['unit'] = isset($deliveries[$i]['order']['order_material'][$j]['material_unit']) ?  $deliveries[$i]['order']['order_material'][$j]['material_unit']['unit'] : '';
                $material['skip_id'] = isset($deliveries[$i]['order']['order_material'][$j]['skip_id']) ?  $deliveries[$i]['order']['order_material'][$j]['skip_id'] : null;
                $material['skip_title'] = isset($deliveries[$i]['order']['order_material'][$j]['skips']) && isset($deliveries[$i]['order']['order_material'][$j]['skips']['asset_inventory']['title']) ?  $deliveries[$i]['order']['order_material'][$j]['skips']['asset_inventory']['title'] : null;

                array_push($order_material, $material);
            }
        }
        $asn_mat_array = TripAssignedMaterial::where('delivery_trip_id',$delivery_trip_id)->get();
        $assigned_materials = $asn_mat_array->toArray();
        foreach($asn_mat_array as $asn_key => $asn_mat){
            $assigned_materials[$asn_key]['material'] = $asn_mat->material->name;
            $assigned_materials[$asn_key]['unit'] = isset($asn_mat->material_unit) && $asn_mat->material_unit != null ? $asn_mat->material_unit->unit : null;
        }
        //count quantity for orders
        if($deliveries[$i]['pickup_service_time'] != NULL && $deliveries[$i]['dropoff_service_time'] != NULL)
        {
            $secs = strtotime($deliveries[$i]['pickup_service_time'])-strtotime("00:00:00");
            $servicetime = date("H:i:s",strtotime($deliveries[$i]['dropoff_service_time'])+$secs);
        }
        else{
            $servicetime=null;
        }
        // return $trip_status;
        
        $delivery_data[] = [
            "order_id" => $deliveries[$i]['order_id'],
            'actual_distance' => $distance,
            'actual_time' => $time,
            "order_status_id" => $deliveries[$i]['order']['order_status_id'],
            "order_status" => json_decode($deliveries[$i]['order']['order_status']['order_status_title']),
            "order_number" => $deliveries[$i]['order']['order_number'],
            'service_time' => $servicetime,
            'pickup_check_in' => $deliveries[$i]['pickup_check_in'],
            'trip_starttime' => $deliveries[$i]['trip_startime'],
            'trip_endtime' => $deliveries[$i]['trip_endtime'],
            'suggested_path'=> $deliveries[$i]['suggested_path'],
            'trip_status' => $trip_status,
            'trip_status_key' => $deliveries[$i]['trip_status']['key'],
            'load' => $deliveries[$i]['load'],
            'dropoff_check_in' => $deliveries[$i]['dropoff_check_in'],
            'unload' => $deliveries[$i]['unload'],
            "created_at" => $deliveries[$i]['created_at'],
            'start_time_planned' => $deliveries[$i]['start_time_planned'],
            'delivery_date' =>  date_format(date_create($deliveries[$i]['trip_date']), "Y-m-d"),
            "kpi" => $kpi,
            "category" => $get_Category,
            "actual_service_time" => $actualservicetime,
            "is_cancellable" => $isCancelable,
            "address_id" => isset($deliveries[$i]['order']['address']) ? $deliveries[$i]['order']['address']['address_id'] : 0,
            "address" => [
                "address_title" => isset($deliveries[$i]['order']['address']) ? $deliveries[$i]['order']['address']['address_title'] : '',
                "address_detail" => isset($deliveries[$i]['order']['address']) ? $deliveries[$i]['order']['address']['address'] : '',
                "latitude" => $lat,
                "longitude" => $long,
            ],
            
            "customer" => [
                "name" => ($deliveries[$i]['order']['customer']['name']),
                "phone" => $deliveries[$i]['order']['customer']['mobile'],
            ],
            "assigned_materials" => $assigned_materials,
            "pickup_material" => $pickup_material,
            "dropoff_material" => $dropoff_material,
            "order_material" => $order_material,
            'pickup_constraints' => $pickconstraints,
            'dropoff_constraints' => $dropconstraints,
        ];

            
        }
        return response()->json([
            "code" => 200,
            "deliveries_count" => count($deliveries),
            "data" => [

                'deliveries' => $delivery_data,

            ],
            "message" => ("Data Loaded!"),
        ]);
    }


    public function updateDeliveryTripAction(Request $request, $store_id)
    {



        $data =  json_decode($request->getContent(), true);

        $rules = [


            'trip_code' => 'required|string|min:1|exists:delivery_trips,trip_code',
            'vehicle_id' => ['required', Rule::exists('vehicles', 'vehicle_id')
                ->where('store_id', $store_id),],
            'trip_date' => 'required|date|date_format:Y-m-d'


        ];

        $user = Auth::user();
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }


        $deliveryUpdates = DeliveryTrip::updateDeliveryTrip($data, $user);
        if ($deliveryUpdates) {

            return response()->json([
                "code" => 200,
                "message" => "Trip Updated SuccessFully"
            ]);
        }

        return response()->json([
            "code" => 422,
            "message" => "Unable to update trip"
        ]);
    }

    public function deleteDeliveryTripAction(Request $request)
    {
        $user = Auth::user();

        $data =  json_decode($request->getContent(), true);

        $rules = [
            'trip_id' => 'required|min:1|exists:delivery_trips,delivery_trip_id',
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {

            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $trip_id = (int)$data['trip_id'];
        $exisitingDeliveryTrip = DeliveryTrip::where('delivery_trip_id', $trip_id)->first();

        $deliveryTripUpdates = DeliveryTrip::deleteTrip($data['trip_id']);

        if ($deliveryTripUpdates) {


            $updatedStatusID = OrderStatus::where('key', 'READY_FOR_PICK_UP')->pluck('order_status_id');

            $updatedStatusID = $updatedStatusID[0];
            $getorders = DeliveryTrip::join('orders', 'orders.order_id', '=', 'deliveries.order_id')
                ->where('status_cancelled', NULL)
                ->where('delivery_trip_id', $trip_id)
                ->get('deliveries.order_id')->toArray();

            $setorders = Order::whereIn('order_id', $getorders)
                ->update(['order_status_id' => $updatedStatusID]);

            for ($i = 0; $i < count($getorders); $i++) {
                $orderlogsdata[] = [

                    'order_id' => $getorders[$i]['order_id'],
                    'order_status_id' => $updatedStatusID,
                    'source_id' => 12,
                    'user_id' => $user->user_id,
                    'created_at' =>  date('Y-m-d H:i:s'),
                    'updated_at' =>  date('Y-m-d H:i:s')


                ];
            }
            OrderLogs::insert($orderlogsdata);

            $deliveryUpdates = DeliveryTrip::deleteDelivery($data['trip_id']);



            return response()->json([
                "code" => 200,
                "message" => "Trip removed SuccessFully"
            ]);
        }

        return response()->json([
            "code" => 422,
            "message" => "Unable to remove trip"
        ]);
    }




    public function createCustomTripAction(Request $request, $store_id)
    
    {
        $user = Auth::user();
        $batch_no = TripBatches::getNextBatchtId("customRouting", $store_id, $user->user_id);
        $batch_no = $batch_no->batch_no;


        $request->request->add(['batch_no' => $batch_no]);

        $data =  $request->getContent();

        $data1 = $request->all();
        
        $requestData = json_decode($data, true);
        $total_trips = count($requestData);
        
        $requestData['batch_no'] = $data1['batch_no'];
        $total_no_trips = 0;

        for ($i = 0; $i < $total_trips; $i++) {

            $rules = [

                'dropoff_location_id' => 'required|int|min:1',
                'vehicle_id' => 'required|int|min:1|exists:vehicles,vehicle_id',
                'order_id' => 'required|min:1',
                'order_id.id' => 'required|exists:orders,order_id|min:1',
                'pickup_scales' => 'array|min:0',
                'dropoff_scales' => 'array|min:0',
                'trip_date' => 'required|date|date_format:Y-m-d',
                'no_of_trips'=>'required|min:1|max:50'
               
            ];

            $validator = Validator::make($requestData[$i], $rules);
            if ($validator->fails()) {


                return responseValidationError('Fields Validation Failed.', $validator->errors());
            }
        }
    
        $user = Auth::user();
      
      
        $total_of_trip = 0;
        $pickup_id = null; //Ayesha: 24-10-2022
        for ($i = 0; $i < $total_trips; $i++) 
        {
            $no_of_trips = $data1[$i]['no_of_trips'];
            $total_of_trip =$total_of_trip+$no_of_trips;
            if(isset($data1[$i]['multi_trip_start']) && $data1[$i]['multi_trip_start'] != "" && $data1[$i]['multi_trip_start'] != null && isset($data1[$i]['multi_trip_end'])  && $data1[$i]['multi_trip_end'] != "" && $data1[$i]['multi_trip_end'] != null){
                $multi_trip_start = $data1[$i]['multi_trip_start'];
                $multi_trip_end = $data1[$i]['multi_trip_end'];
            }
            else{
               
                $multi_trip_start = $data1[$i]['trip_date'];
                $multi_trip_end =   $data1[$i]['trip_date'];
            }
        
            $to = \Carbon\Carbon::createFromFormat('Y-m-d', $multi_trip_end);
            $from = \Carbon\Carbon::createFromFormat('Y-m-d', $multi_trip_start);
            $diff_in_days = 1+$to->diffInDays($from);
            
            $remainder =$no_of_trips%$diff_in_days;
            
            $last_day_trip = 0; $days_for_loop = 0;
            if($remainder == 0){
                $trip_per_day = $no_of_trips/$diff_in_days;
                $days_for_loop = $diff_in_days;
            }
            else{
                $trip_per_day = floor($no_of_trips/$diff_in_days);
                $assign_trip = $trip_per_day * ($diff_in_days-1);
                $reamin_trip = $no_of_trips-$assign_trip;
                $days_for_loop = $diff_in_days - 1;
                $last_day_trip = $reamin_trip;
            }
            for($l=0; $l<$days_for_loop; $l++)
            {
                $cur_date = date('Y-m-d', strtotime("+".$l." day", strtotime($multi_trip_start)));
               for($n=0; $n<$trip_per_day; $n++){
                    $requestData[$i]['batch_no'] = $data1['batch_no'];
                   
                    $check_category = checkOrderCatgeory($data1[$i]['order_id']['id']);
                    if($check_category == "ASSET"){
                        if(isset($data1[$i]['trip_material'][0]['material_id']) && $data1[$i]['trip_material'][0]['material_id'] != null )
                        {   
                            $trans_source_yard = getAssetTransactionSource('Yard');
                            $yard_id = \App\Model\AssetInventory::where('asset_id',$data1[$i]['trip_material'][0]['material_id'])->where('assigned_to',$trans_source_yard)->value('assignee_id');
                            if(!isset($yard_id) || $yard_id == NULL){
                                return $response = [
                                    "code" => 500,
                                    "data" => "",
                                    'message' => 'Asset not found.'
                                ];
                            }
                            $pickup_id = $yard_id;
                        }else{
                            return $response = [
                                "code" => 500,
                                "data" => "",
                                'message' => 'Kindly select asset to proceed'
                            ];
                        }
                    }
                    
                    $dropoff_id = $data1[$i]['dropoff_location_id'];
                    $getvehiclelocation = Vehicle::where('vehicle_id', $requestData[$i]['vehicle_id'])
                        ->get(['current_latitude', 'current_longitude','driver_id','erp_id','vehicle_plate_number'])->toArray();
                        if(!count($getvehiclelocation) > 0){
                            
                            return $response = [
                                "code" => 500,
                                "data" => "",
                                'message' => 'vehicle does not exist.'
                            ];
                        }

                    $lat = $getvehiclelocation[0]['current_latitude'];
                    $lng = $getvehiclelocation[0]['current_longitude'];
                    $driver_id = $getvehiclelocation[0]['driver_id'];
                    $trip_data = $requestData[$i];
                    
                    $trip_data['trip_date'] =  $cur_date;
                    $deliveryTrip = DeliveryTrip::createDeliveryTrip($trip_data, $user->user_id, $store_id, $requestData[$i]['vehicle_id'],$driver_id,$pickup_id);
                    $deliveries = DeliveryTrip::createDeliveries($deliveryTrip->delivery_trip_id, $trip_data, $lat, $lng, $user->user_id,$pickup_id,$dropoff_id);
                    //Trip Materials Insertion 
                    
                    if(count($requestData[$i]['trip_material'])>0)
                    {
                        for($j=0;$j<count($requestData[$i]['trip_material']);$j++)
                        {   
                            if($check_category == "ASSET"){
                                $temp_assets =  (int)$requestData[$i]['trip_material'][$j]['material_id'];
                                $material_id = \App\Model\OrderServiceRequest::whereJsonContains('temp_assets',$temp_assets)->value('material_id');
                                $asset_id = isset($requestData[$i]['trip_material'][$j]['material_id']) ? $requestData[$i]['trip_material'][$j]['material_id'] : NULL;
                                $requestData[$i]['trip_material'][$j]['material_id'] = $material_id;
                            }
                            $trip_Materials = TripAssignedMaterial::insert([
                                'delivery_trip_id'=> $deliveryTrip->delivery_trip_id,
                                'material_id' =>  $requestData[$i]['trip_material'][$j]['material_id'],
                                'weight' => isset($requestData[$i]['trip_material'][$j]['quantity'])? $requestData[$i]['trip_material'][$j]['quantity']:NULL,
                                'unit' => isset($requestData[$i]['trip_material'][$j]['unit']) && $requestData[$i]['trip_material'][$j]['unit'] != "" && $requestData[$i]['trip_material'][$j]['unit'] != null? $requestData[$i]['trip_material'][$j]['unit']:NULL,
                                'skip_id' => isset($requestData[$i]['trip_material'][$j]['skip_id'])? $requestData[$i]['trip_material'][$j]['skip_id']:NULL,
                                'asset_id' => isset($asset_id)? $asset_id:NULL,
                                'created_at' =>  date('Y-m-d H:i:s'),
                            ]);
                            
                        }
                    
                    }
                
                    $trip_code = $deliveryTrip->trip_code;
                    $result = $this->tripnotification($requestData[$i]['vehicle_id'], $deliveryTrip->delivery_trip_id,"Trip Created","Control Tower Assigned You A Trip. Trip Code is " . $trip_code);
               }
            }
            if($last_day_trip > 0){
                $cur_date = date('Y-m-d', strtotime("+".$days_for_loop." day", strtotime($multi_trip_start)));
                for($rem_count=0; $rem_count < $last_day_trip; $rem_count++){ 
                    $requestData[$i]['batch_no'] = $data1['batch_no'];
                    $check_category = ($data1[$i]['order_id']['id']);
                
                    if($check_category == "ASSET"){
                        if(isset($data1[$i]['trip_material'][0]['material_id']) && $data1[$i]['trip_material'][0]['material_id'] != null )
                        {  
                            $trans_source_yard = getAssetTransactionSource('Yard');
                            $yard_id = \App\Model\AssetInventory::where('asset_id',$data1[$i]['trip_material'][0]['material_id'])->where('assigned_to',$trans_source_yard)->value('assignee_id');
                            if(!isset($yard_id) || $yard_id == NULL){
                                return $response = [
                                    "code" => 500,
                                    "data" => "",
                                    'message' => 'Asset not found.'
                                ];
                            }
                            
                            $pickup_id = $yard_id;
                        }else{
                            return $response = [
                                "code" => 500,
                                "data" => "",
                                'message' => 'Kindly select asset to proceed'
                            ];
                        }
                    }
                    
                    $dropoff_id = $data1[$i]['dropoff_location_id'];
                    $getvehiclelocation = Vehicle::where('vehicle_id', $requestData[$i]['vehicle_id'])
                        ->get(['current_latitude', 'current_longitude','driver_id','erp_id','vehicle_plate_number'])->toArray();
                        if(!count($getvehiclelocation) > 0){
                            
                            return $response = [
                                "code" => 500,
                                "data" => "",
                                'message' => 'vehicle does not exist.'
                            ];
                        }

                    $lat = $getvehiclelocation[0]['current_latitude'];
                    $lng = $getvehiclelocation[0]['current_longitude'];
                    $driver_id = $getvehiclelocation[0]['driver_id'];
                    $trip_data = $requestData[$i];
                  
                    $trip_data['trip_date'] =  $cur_date;
                    $deliveryTrip = DeliveryTrip::createDeliveryTrip($trip_data, $user->user_id, $store_id, $requestData[$i]['vehicle_id'],$driver_id,$pickup_id);
                    $deliveries = DeliveryTrip::createDeliveries($deliveryTrip->delivery_trip_id, $trip_data, $lat, $lng, $user->user_id,$pickup_id,$dropoff_id);
                    //Trip Materials Insertion 
                    
                    if(count($requestData[$i]['trip_material'])>0)
                    {
                        for($j=0;$j<count($requestData[$i]['trip_material']);$j++)
                        {   
                            if($check_category == "ASSET"){
                                $temp_assets =  (int)$requestData[$i]['trip_material'][$j]['material_id'];
                                $material_id = \App\Model\OrderServiceRequest::whereJsonContains('temp_assets',$temp_assets)->value('material_id');
                                $asset_id = isset($requestData[$i]['trip_material'][$j]['material_id']) ? $requestData[$i]['trip_material'][$j]['material_id'] : NULL;
                                $requestData[$i]['trip_material'][$j]['material_id'] = $material_id;
                            }
                            $trip_Materials = TripAssignedMaterial::insert([
                                'delivery_trip_id'=> $deliveryTrip->delivery_trip_id,
                                'material_id' =>  $requestData[$i]['trip_material'][$j]['material_id'],
                                'weight' => isset($requestData[$i]['trip_material'][$j]['quantity'])? $requestData[$i]['trip_material'][$j]['quantity']:NULL,
                                'unit' => isset($requestData[$i]['trip_material'][$j]['unit']) && $requestData[$i]['trip_material'][$j]['unit'] != "" && $requestData[$i]['trip_material'][$j]['unit'] != null? $requestData[$i]['trip_material'][$j]['unit']:NULL,
                                'skip_id' => isset($requestData[$i]['trip_material'][$j]['skip_id'])? $requestData[$i]['trip_material'][$j]['skip_id']:NULL,
                                'asset_id' => isset($asset_id)? $asset_id:NULL,
                                'created_at' =>  date('Y-m-d H:i:s'),
                            ]);
                            
                        }
                    }
                    $trip_code = $deliveryTrip->trip_code;
                    $result = $this->tripnotification($requestData[$i]['vehicle_id'], $deliveryTrip->delivery_trip_id,"Trip Created","Control Tower Assigned You A Trip. Trip Code is " . $trip_code);
               } 
            }
        }
        if ($deliveries['code'] == 200) {

            $getOrderStatus = OrderStatus::where('key', 'EXECUTION')->pluck('order_status_id')->first();
            $getOrderStatusID = Order::where('order_id', $requestData[0]['order_id']['id'])
                ->pluck('order_status_id')->first();
            if ($getOrderStatusID == 16) {
                $updateOrderStatus = Order::where('order_id', $requestData[0]['order_id']['id'])
                    ->update(['order_status_id' => $getOrderStatus]);
                $orderlogsdata[] = [

                    'order_id' => $requestData[0]['order_id']['id'],
                    'order_status_id' => $getOrderStatus,
                    'source_id' => 12,
                    'user_id' => $user->user_id,
                    'created_at' =>  date('Y-m-d H:i:s'),
                    'updated_at' =>  date('Y-m-d H:i:s')


                ];
                OrderLogs::insert($orderlogsdata);
                
            }





            try {


                //update batch
                $batchUpdate = TripBatches::where('batch_no', $requestData['batch_no'])->update([
                    "total_orders" => 1,
                    "dropped_orders" => 0,
                    "no_of_vehicles" =>  $total_trips,
                    "no_of_trips" => $total_trips,
                    "plan_date" => $requestData[0]['trip_date'],
                    "is_created" => true,
                    "execution_date" => date("Y-m-d H:i:s"),
                    "batch_type" => 'NOW',
                    "constraints" => '{"Allocation":"N-A","Optimization":"N-A","multiTrip":"N-A","sequence_order":{"order":null,"values":[]}}',
                    "max_distance" => '0',
                    "no_of_solutions" => 1
                ]);


                $response = [
                    "code" => 200,
                    "data" => [
                        "processing_results" => [
                            "batch_no" => $requestData['batch_no'],
                            "trip_id" => isset($deliveryTrip) && isset($deliveryTrip->delivery_trip_id) ? $deliveryTrip->delivery_trip_id : null,
                            "end_time_script" => date("Y-m-d H:i:s"),
                            "no_total_orders" => 1,
                            "number_of_trips" => $total_of_trip,
                            "number_of_vehicles" => $total_trips
                        ],
                        "error" => ''
                    ],
                    'message' => 'Congratulations!Custom trips created successfully.'
                ];
            } catch (\Exception $ex) {
                $response = [
                    "code" => 500,
                    "data" => [
                        "batch_no" => $requestData['batch_no'],

                        "processing_results" => ["Error in processing!"],
                        "error" => $ex->getMessage()
                    ],
                    'message' => 'Error in custom trip creation!.'
                ];
            }
        } else {
            $response = [
                "code" => 500,
                "data" => [
                    "batch_no" => $requestData['batch_no'],
                    "error" => $deliveries['error']
                ],
                'message' => 'Error in Trip Creation!'
            ];
        }
        // tripnotification($vehicle_id, $trip_id, "Trip Created", "Control Tower Assigned You A Trip. Trip Code is " .  $trip_code);
        // $getuserid =  Vehicle::where('vehicle_id', $vehicle_id)->pluck('driver_id');

        // $notification_id = User::where('user_id', $getuserid[0])->pluck('fcm_token_for_driver_app');
        // $source = 1;  
        // $is_sent = 0;
        // if ($notification_id != "" || $notification_id != null) {
        // $message = "Control Tower Assigned You A Trip. Trip ID is " . $trip_id;
        // $title ="Trip Created";
        // $type = "basic";
        // $res = send_notification_FCM($notification_id, $title, $message, $type, $source);
        // $is_sent = 1;
        // }



        return response()->json($response);
    }





    public function generateDynamicTripsAction(Request $request, $store_id)
    {


        $user = Auth::user();
        $batch_no = TripBatches::getNextBatchtId("dynamicRouting", $store_id, $user->user_id);
        $batch_no = $batch_no->batch_no;
        $request->request->add(['batch_no' => $batch_no]);

        $data =  $request->getContent();
        $data1 = $request->all();

        $requestData = json_decode($data, true);

        $requestData['batch_no'] = $data1['batch_no'];


        $rules = [

            'set_now' => 'required|string',
            'is_approved' => 'required|string',
            'order_ids' => 'present|array|min:0',
            'constraints' => 'required|array',
            'trip_date' => 'required|date',
            'vehicle_ids' => 'nullable|array',
            'vehicle_ids.*' => 'nullable|int',
            'startDate' => 'required|date|date_format:Y-m-d',
            'endDate' => 'required|date|date_format:Y-m-d',
            'maxDist' => 'required|int|min:0',
            'servicetime' => 'required|int|min:0'


        ];
        $validator = Validator::make($requestData, $rules);
        if ($validator->fails()) {

            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        if (array_key_exists("testEnv2", $requestData) && $requestData['testEnv2'] == true) {
            $url = 'http://192.168.18.33:8000/';
        }
        if (array_key_exists("testEnv1", $requestData) && $requestData['testEnv1'] == true) {
            $url = 'http://192.168.18.139:8000/';
        } else {


            $url = 'http://localhost:8002/';
            // $url='192.168.18.63:8000/';

        }

        $key = DB::table('api_keys')
            ->where("name", 'apipublickey')
            ->pluck('key')->toArray();
        $key = $key[0];
        $headers = array(
            "X-Api-Key:" . $key,
            "Content-Type: application/json"
        );



        $user = Auth::user();


        $requestData['vehicle_ids'] = array_map(function ($value) {
            return intval($value);
        }, $requestData['vehicle_ids']);


        if ($requestData['set_later'] == "true") {

            $cronjob = array(
                'store_id' => $store_id,
                'from_date' => $requestData['startDate'],
                'max_dist' => $requestData['maxDist'],
                'to_date' => $requestData['endDate'],
                'is_approved' => $requestData['is_approved'],
                'order_ids' => json_encode($requestData['order_ids'], true),
                'created_by' => $user->user_id,
                'trip_date' => $requestData['trip_date'],
                'veh_list' => json_encode($requestData['vehicle_ids'], true),
                'channel_ids' => json_encode($requestData['channels'], true),
                'constraints' => json_encode($requestData['constraints'], true),
                'service_time' => $requestData['servicetime'],
                'cron_time' => $requestData['trip_date']



            );


            CronJobs::insert($cronjob);




            $url = 'http://localhost:8002/cron';




            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cronjob));
            $result = curl_exec($ch);
            $data = json_decode($result, true);

            curl_close($ch);
            if ($data['code'] == 200) {
                $response = [
                    "code" => $data['code'],
                    "data" => [],
                    'message' => 'Congratulations!' . $data['message']
                ];
            } else {
                $response = [
                    "code" => $data['code'],
                    "data" => [],
                    'message' => $data['message']
                ];
            }
            return response()->json($response);
        } else {
            $crondata = array(
                'sal_off_id' => $store_id,
                'from_date' => $requestData['startDate'],
                'max_dist' => $requestData['maxDist'],
                'to_date' => $requestData['endDate'],
                'is_approved' => $requestData['is_approved'],
                'order_ids' => $requestData['order_ids'],
                'created_by' => $user->user_id,
                'trip_date' => $requestData['tripDate2'],
                'veh_list' => $requestData['vehicle_ids'],
                'channel_ids' => $requestData['channels'],
                'constraints' => $requestData['constraints'],
                'override_working_hours' => $requestData['override_working_hours'],
                'batch_no' => $requestData['batch_no'],
                'service_time_allow' => $requestData['servicetime'],
                'return_to_warehouse' => 1
            );



            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($crondata));
            $result = curl_exec($ch);
            curl_close($ch);


            $data = json_decode($result, true);

            if (!isset($data['code'])) {
                $response = [
                    "code" => 204,
                    'data' => $data,
                    'message' => 'Trip Cannot Be Created'
                ];
                return response()->json($response);
            }






            if ($data['code'] == 200) {





                //check if constraints available then update
                //OLD
                $oldConstraints = StoreConstraints::where('store_id', $store_id)->get();
                if (!$oldConstraints->isEmpty()) {
                    $newconstraints = json_encode($requestData['constraints'], true);
                    $storeConstraintsObj = new StoreConstraints;

                    $storeConstraints = $storeConstraintsObj->updateStoreConstraints($oldConstraints, $newconstraints);
                } else {
                    //save constraints
                    $storeConstraints = new StoreConstraints;
                    $storeConstraints = $storeConstraints->addStoreConstraints($requestData, $store_id, $user->user_id);
                }

                $currentbatchno = $requestData['batch_no'];


                $storeConstraints = TripBatches::where('batch_no', $currentbatchno)->get();
                if (!$storeConstraints->isEmpty()) {
                    $getconstraints = json_encode($requestData['constraints'], true);
                    $storeConstraintsObj = new TripBatches;
                    $storeConstraints = $storeConstraintsObj->updateStoreConstraints($storeConstraints, $getconstraints);
                } else {
                    //save constraints
                    $storeConstraints = new TripBatches;
                    $storeConstraints = $storeConstraints->addStoreConstraints($requestData, $store_id, $user->user_id);
                }







                $responseWebArray = array();
                //check order statuses and log them and log trip as well
                if (count($data['data']['trips']) > 0) {

                    if (count($data['data']['trips']) > 1) {
                        for ($i = 0; $i < count($data['data']['trips']); $i++) {
                            $vehicleid = $data['data']['trips'][$i]['vehicle_id'];
                            $tripid = $data['data']['trips'][$i]['trip_id'];
                            $vehicleid = (int)$vehicleid;
                            $result = $this->tripnotification($vehicleid, $tripid, "Trip Created", "Control Tower Assigned You A Trip. Trip Code is " . $data['data']['trips'][$i]['trip_code']);
                        }
                    } else {
                        $vehicleid = $data['data']['trips'][0]['vehicle_id'];
                        $tripid = $data['data']['trips'][0]['trip_id'];
                        $vehicleid = (int)$vehicleid;
                        $result = $this->tripnotification($vehicleid, $tripid, "Trip Created", "Control Tower Assigned You A Trip. Trip Code is " . $data['data']['trips'][0]['trip_code']);
                    }



                    $order_status_id = '';

                    $order_ids = array();
                    foreach ($data['data']['trips'] as $key => $trip) {




                        $logdata[] = [

                            'trip_id' => $trip['trip_id'],
                            // 'order_id' => $data['order_id']['id'],
                            'trip_status_id' => 1,
                            'created_at' =>  date('Y-m-d H:i:s')


                        ];

                        TripLogs::insert($logdata);
                        if (count($trip['trip_orders']) > 0) {

                            foreach ($trip['trip_orders'] as $key => $order) {
                                array_push($order_ids, $order);
                                $orderLogInApp[] = [
                                    'order_id' => $order,
                                    'order_status_id' => $order_status_id,
                                    'user_id' => $user->user_id,
                                    'source_id' => 1
                                ];
                            }
                        }
                    }
                    try {


                        //update orders status
                        if (array_key_exists("testEnv2", $requestData) && $requestData['testEnv2'] == true) {
                            $url = 'http://192.168.18.33:8000/';
                        } elseif (array_key_exists("testEnv1", $requestData) && $requestData['testEnv1'] == true) {
                            $url = 'http://192.168.18.139:8000/';
                        } else {
                        }

                        //update batch table
                        $workingScope = $data['data']['working'][0];

                        $batchUpdate = TripBatches::where('batch_no', $workingScope['batch_no'])->update([
                            "total_orders" => $workingScope['no_total_orders'],
                            "dropped_orders" => $workingScope['no_dropped_orders'],
                            "no_of_vehicles" => $workingScope['number_of_vehicles'],
                            "no_of_trips" => $workingScope['number_of_trips'],
                            "max_distance" =>  $requestData['maxDist'],
                            "plan_date" => $requestData['trip_date'],
                            "is_created" => true,
                            "execution_date" => $workingScope['end_time_script'],
                            "batch_type" => 'NOW',
                            "batch_cost" => $workingScope['gross_amount_all_trips'],
                            "batch_gas_cost" => $workingScope['gas_cost_all_trips'],
                            "no_of_solutions" => $workingScope['no_of_solution']



                        ]);



                        $response = [
                            "code" => $data['code'],
                            "data" => [
                                "batch_no" => $workingScope['batch_no'],
                                "processing_results" => $workingScope,
                                "error" => ''
                            ],
                            'message' => 'Congratulations.' . $data['message']
                        ];
                        $requestLogUpdate = array(
                            'request_path_out' => $url,
                            'request_out' => $crondata,
                            'response_in' => $data,
                            'response_out' => $response,
                            'ref' => array(
                                '__ACTION__' => 'DYNAMIC__TRIP__REQUEST',
                            )
                        );
                    } catch (\Exception $ex) {
                        $response = [
                            "code" => $data['code'],
                            "data" => [
                                "batch_no" => $requestData['batch_no'],
                                "processing_results" => ["Error in processing!"],
                                "error" => $ex->getMessage()
                            ],
                            'message' => $data['message']
                        ];
                        $requestLogUpdate = array(
                            'request_path_out' => $url,
                            'request_out' => $crondata,
                            'response_in' => $data,
                            'response_out' => $response,
                            'ref' => array(
                                '__ACTION__' => 'DYNAMIC__TRIP__REQUEST',
                            )
                        );
                    }
                    //@end try catch
                } else {
                    $response = [
                        "code" => $data['code'],
                        "data" => [
                            "batch_no" => $requestData['batch_no'],
                            "processing_results" => ["Error in processing!"],
                            "error" => "No trips created!"
                        ],
                        'message' => $data['message']
                    ];
                    $requestLogUpdate = array(
                        'request_path_out' => $url,
                        'request_out' => $crondata,
                        'response_in' => $data,
                        'response_out' => $response,
                        'ref' => array(
                            '__ACTION__' => 'DYNAMIC__TRIP__REQUEST',
                        )
                    );
                }
                //@trips count if end here        
            } elseif ($data['code'] == '424') {
                $response = [
                    "code" => $data['code'],
                    "data" => [
                        "batch_no" => $requestData['batch_no'],
                        "trip_code" => '',
                        "error" => ''
                    ],
                    'message' => $data['message']
                ];
                $requestLogUpdate = array(
                    'request_path_out' => $url,
                    'request_out' => $crondata,
                    'response_in' => $data,
                    'response_out' => $response,
                    'ref' => array(
                        '__ACTION__' => 'DYNAMIC__TRIP__REQUEST',
                    )
                );
            } elseif ($data['code'] == '404') {
                $response = [
                    "code" => $data['code'],
                    "data" => [
                        "batch_no" => $requestData['batch_no'],
                        "trip_code" => '',
                        "error" => ''
                    ],
                    'message' => $data['message']
                ];
                $requestLogUpdate = array(
                    'request_path_out' => $url,
                    'request_out' => $crondata,
                    'response_in' => $data,
                    'response_out' => $response,
                    'ref' => array(
                        '__ACTION__' => 'DYNAMIC__TRIP__REQUEST',
                    )
                );
            } elseif ($data['code'] == '' || $data['code'] == null) {
                $response = [
                    "code" => 502,
                    "data" => [
                        "batch_no" => $requestData['batch_no'],
                        "trip_code" => '',
                        "error" => ''
                    ],
                    'message' => 'Server down Please try again later'
                ];
                $requestLogUpdate = array(
                    'request_path_out' => $url,
                    'request_out' => $crondata,
                    'response_in' => $data,
                    'response_out' => $response,
                    'ref' => array(
                        '__ACTION__' => 'DYNAMIC__TRIP__REQUEST',
                    )
                );
            } else {
                $response = [
                    "code" => $data['code'],
                    "data" => [
                        "batch_no" => $requestData['batch_no'],
                        "trip_code" => '',
                        "error" => ''
                    ],
                    'message' => $data['message']
                ];
                $requestLogUpdate = array(
                    'request_path_out' => $url,
                    'request_out' => $crondata,
                    'response_in' => $data,
                    'response_out' => $response,
                    'ref' => array(
                        '__ACTION__' => 'DYNAMIC__TRIP__REQUEST',
                    )
                );
            }


            return response()->json($response);
        }

        //add constraints in table with store_id
    }


    public function ApproveRejectTripAction(Request $request, $store_id)
    {

        $user = Auth::user();


        $data =  json_decode($request->getContent(), true);
        $rules = [
            'trip_id' => [
                'required', Rule::exists('delivery_trips', 'delivery_trip_id')
                    ->where('store_id', $store_id)

            ]
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        try {
            \DB::beginTransaction();

            $exisitingDeliveryTrip =
                DeliveryTrip::where('delivery_trip_id', $data['trip_id'])
                ->first();

            if (gettype($exisitingDeliveryTrip) === "object") {

                $tripMeta = json_encode($exisitingDeliveryTrip->toArray(), true);
                $to = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', date('Y-m-d H:s:i'));
                $from = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $exisitingDeliveryTrip->trip_date);
                $diff_in_days = $to->diffInDays($from);

                if ($data['value'] == 'true') {
                    $statusid = 1;
                    $deliveryTripUpdate = DeliveryTrip::where('delivery_trip_id', $data['trip_id'])->update(['is_approved' => $data['value'], 'trip_status_id' => $statusid]);
                } else {
                    $statusid = 5;
                    $deliveryTripUpdate = DeliveryTrip::where('delivery_trip_id', $data['trip_id'])->update(['is_approved' => $data['value'], 'trip_status_id' => $statusid]);
                }

                if ($deliveryTripUpdate) {
                    \DB::commit();


                    $logdata = [
                        'delivery_trip_id' => $data['trip_id'],
                        'template_id' => 10,
                        'ref' => array(
                            '__TRIP_ID__' => $data['trip_id'],
                            '__FLAG__' => 'TRIP__APPROVED',
                            '__TRIP_META__' => $tripMeta
                        ),
                        "logg_date" => date('Y-m-d H:i:s')

                    ];



                    return response()->json([
                        "code" => 200,
                        "data" => [
                            "trip_id" => $data['trip_id'],
                            "error" => ''
                        ],
                        'message' => 'Trip Updated SuccessFully!'
                    ]);
                } else {
                    \DB::rollBack();
                    return response()->json([
                        "code" => 422,
                        "data" => [
                            "trip_id" => $data['trip_id'],
                            "error" => ''
                        ],
                        'message' => 'Un-able to Update Trip!'
                    ]);
                }
            } else {
                return response()->json([
                    "code" => 422,
                    "data" => [
                        "trip_id" => $data['trip_id'],
                        "error" => ''
                    ],
                    'message' => 'No Trip Loaded!'
                ]);
            }
        } catch (Exception $ex) {
            Error::trigger("request.add", [$ex->getMessage()]);
            return response()->json([
                "code" => 422,
                "data" => [
                    "trip_id" => $data['trip_id'],
                    "error" => ''
                ],
                'message' => $ex->getMessage()
            ]);
        }
    }
    
    function tripnotification($vehicle_id, $trip_id, $title, $message)
    {
        $getuserid =  Vehicle::where('vehicle_id', $vehicle_id)->pluck('driver_id');
        if($getuserid->isEmpty()){
            return response()->json([
                "code" => 400,
                "errors" => " Vehicle/Driver does not exist for this Trip. "
            ]);

        }
        $notification_id = User::where('user_id', $getuserid[0])->pluck('fcm_token_for_driver_app');
        $notification_id = $notification_id[0];
        $source = 1;  
        $is_sent = 0;
        if ($notification_id != "" || $notification_id != null) {
            $type = "basic";
            $res = send_notification_FCM($notification_id, $title, $message, $type, $source);
        }
    }

    public function updateTripAction(Request $request, $store_id)
    {
        $errors = [];

        $data =  $request->getContent();
        $data1 = $request->all();

        $requestData = json_decode($data, true);

        $data =  json_decode($request->getContent(), true);
        $rules = [


            'delivery_trip_id' => 'required|int|min:1|exists:delivery_trips,delivery_trip_id',
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'vehicle_id')
                ->where('store_id', $store_id),],
            'trip_date' => 'nullable|date|date_format:Y-m-d',
            'removedOrders' => 'nullable|array|distinct',
            'removedOrders.*' => ['nullable', Rule::exists('orders', 'order_id')],
            'addedOrders' => 'nullable|array|distinct',
            'addedOrders.*' => ['nullable', Rule::exists('orders', 'order_id')
                ->where('order_status_id', '14'),]


        ];




        $validator = Validator::make($requestData, $rules);
        if ($validator->fails()) {

            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $user = Auth::user();
        $deliveryUpdates = DeliveryTrip::updateDeliveryTrip($data, $user);

        if (isset($requestData['removedOrders']) && count($requestData['removedOrders']) == 0 && isset($requestData['addedOrders']) && count($requestData['addedOrders']) == 0) {
            if ($deliveryUpdates) {

                return response()->json([
                    "code" => 200,
                    "message" => "Trip Updated SuccessFully"
                ]);
            }

            return response()->json([
                "code" => 422,
                "message" => "Unable to update trip"
            ]);
        }

        $checkorder = DeliveryTrip::where('delivery_trip_id', $requestData['delivery_trip_id'])->get();






        $checktripstatus = DeliveryTrip::where('delivery_trip_id', $requestData['delivery_trip_id'])->first();
        if (!isset($checktripstatus)) {
            return response()->json([
                "code" => 422,
                "message" => "Trip Not Found"
            ]);
        }
        $batch_no = $checktripstatus->batch_no;






        if ($checktripstatus->delivery_trip_type == 'Dynamic') {



            if (isset($requestData['removedOrders']) && count($requestData['removedOrders']) > 0) {
                $tripstatus = $checktripstatus->trip_status_id;

                $a = 0;
                foreach ($requestData['removedOrders'] as $order[$a]) {


                    $current_date_time = Carbon::now()->toDateTimeString();



                    $getvehiclestocks = VehicleStocks::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                        ->select('product_id', 'qty')
                        ->get()
                        ->groupby('product_id', 'qty')->toArray();
                    $getorderitems = OrderItem::where('order_id', $order[$a])
                        ->select('product_id', 'quantity')
                        ->get()
                        ->groupby('product_id', 'quantity')->toArray();
                    $getvehiclestocks = array_values($getvehiclestocks);
                    $getorderitems = array_values($getorderitems);






                    for ($i = 0; $i < count($getvehiclestocks); $i++) {

                        for ($j = 0; $j < count($getorderitems); $j++) {


                            if ($getvehiclestocks[$i][$i]['product_id'] == $getorderitems[$j][$j]['product_id']) {
                                $total = 0;
                                $total = $getvehiclestocks[$i][$i]['qty'];

                                $cal = $getorderitems[$j][$j]['quantity'];
                                $total = ($total - $cal);
                                $tableupdate =  DB::table('vehicle_stocks')
                                    ->where('product_id', $getorderitems[$j][$j]['product_id'])
                                    ->update(['qty' => $total]);
                            }
                        }
                    }



                    if ($requestData['flag'] == 'true') {
                    } else {
                        $dateupdate = DeliveryTrip::where('order_id', $order[$a])
                            ->update(array('deleted_at' =>  $current_date_time));
                    }




                    $updated_status = 14;

                    $orderremove = Order::where('order_id', $order[$a])
                        ->update(array('order_status_id' => $updated_status))
                        && (array('updated_at' =>  $current_date_time));
                    $orderstatusid = DB::table('orders')->where('order_id', $order[$a])
                        ->get()->toArray();
                    $orderlogsdata[] = [

                        'order_id' => $order[$a],
                        'order_status_id' => $orderstatusid[0]->order_status_id,
                        'source_id' => 12,
                        'user_id' => $user->user_id,
                        'created_at' =>  date('Y-m-d H:i:s'),
                        'updated_at' =>  date('Y-m-d H:i:s')


                    ];
                }
                OrderLogs::insert($orderlogsdata);
                if (count($requestData['addedOrders']) == 0) {
                    $result = $this->removeorders($requestData, $user, $tripstatus);
                    if ($checktripstatus->trip_status_id == 2) {

                        $trip_id = $requestData['delivery_trip_id'];
                        $data = json_decode($result, true);
                        
                        $getuserid =  Vehicle::where('vehicle_id', $checktripstatus->vehicle_id)->pluck('driver_id');
                        $source=1;
                        $notification_id =  User::where('user_id', $getuserid[0])->pluck('fcm_token_for_driver_app');
                        $notification_id = $notification_id[0];

                        $title = "trip_Cancelled";
                        $message = "Order Has Been Cancelled Against Trip ID #" . $checktripstatus->trip_code;
                        $id = $getuserid[0];
                        $type = "basic";

                        $res = send_notification_FCM($notification_id, $title, $message, $type, $source);
                    }



                    if ($requestData['flag'] == 'true') {

                        $updated_status = 6;

                        $orderremove = Order::where('order_id', $order[$a])
                            ->update(array('order_status_id' => $updated_status))
                            && (array('updated_at' =>  $current_date_time));
                        $response = [
                            "code" => 200,

                            'message' => 'Order Has Been Cancelled'
                        ];

                        return response()->json($response);
                    }

                    try {



                        $response = [
                            "code" => 200,

                            'message' => 'Dynamic trip updated Successfully',
                        ];

                        return response()->json($response);
                    } catch (\Exception $ex) {
                        return response()->json([
                            "code" => 500,
                            "message" => $ex->getMessage()
                        ]);
                    }
                }
            }
            //For Added Orders 
            if (isset($requestData['addedOrders']) && count($requestData['addedOrders']) > 0) {


                try {
                    //check delivery_trip and status
                    $user = Auth::user();
                    $trip = DeliveryTrip::where('delivery_trip_id', $requestData['delivery_trip_id'])->first();
                    //@if delivery trip is not yet started 
                    if ($trip->trip_startime == null) {
                        //if date and vehicle upates
                        $deliveryUpdates = DeliveryTrip::updateDeliveryTrip($trip, $requestData, $user);
                        //if added orders 
                        $tripMeta = json_encode($trip->toArray(), true);

                        if (isset($requestData['addedOrders']) && count($requestData['addedOrders']) > 0) {


                            $tripstatus = $checktripstatus->trip_status_id;

                            $result = $this->addOrders($requestData, $user, $tripstatus);
                            $data = json_decode($result, true);
                            if ($data['code'] == 200) {
                                // $notification = $this->sendnotification($checktripstatus->vehicle_id,$requestData['delivery_trip_id']);

                                $response = [
                                    "code" => $data['code'],
                                    "data" => [
                                        "wroking" => $data['data']['working'],
                                        "error" => ''
                                    ],
                                    'message' => 'Congratulations.' . $data['message']
                                ];
                            } else {


                                $response = [
                                    "code" => $data['code'],
                                    "data" => [
                                        "wroking" => $data['data']['working'],

                                        "error" => ''
                                    ],
                                    'message' => $data['message']
                                ];
                            }

                            $requestLogUpdate = array(
                                'request_path_out' => 'http://localhost:8002/update',
                                'request_out' => array(
                                    'is_approved' => true, 'order_ids' => $requestData['addedOrders'], 'updated_by' => $user->user_id, 'trip_id' => $requestData['delivery_trip_id'],
                                ),
                                'response_in' => $data,
                                'response_out' => $response,
                                'ref' => array(
                                    '__ACTION__' => 'TRIP__UPDATED',
                                    '_TRIP_META_' => $tripMeta
                                )
                            );


                            $logdata = [

                                'delivery_trip_id' => $trip->delivery_trip_id,
                                'template_id' => 12,
                                'ref' => array(
                                    '__TRIP_ID__' => $trip->delivery_trip_id,
                                    '__FLAG__' => 'TRIP__UPDATED',
                                    '__USER_ID__' => $user->user_id,
                                    '_TRIP_META_' => $tripMeta,
                                    '_INTERNAL_RESPONSE_' => $deliveryUpdates
                                ),
                                "logg_date" => date('Y-m-d H:i:s')
                            ];




                            return response()->json($response);
                        }
                    } elseif ($trip->trip_startime != null && $trip->trip_endtime == null) {
                    } else {
                    }
                } catch (\Exception $ex) {
                    return $ex->getMessage();
                }
            }
        } else {
            if (isset($requestData['removedOrders']) && count($requestData['removedOrders']) > 0) {
                $i = 0;
                foreach ($requestData['removedOrders'] as $order[$i]) {



                    $current_date_time = Carbon::now()->toDateTimeString();
                    $getuserid = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                        ->get('created_by');

                    $getvehiclestocks = VehicleStocks::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                        ->select('product_id', 'qty')
                        ->get()
                        ->groupby('product_id', 'qty')->toArray();
                    $getorderitems = OrderItem::where('order_id', $order[$i])
                        ->select('product_id', 'quantity')
                        ->get()
                        ->groupby('product_id', 'quantity')->toArray();
                    $getvehiclestocks = array_values($getvehiclestocks);
                    $getorderitems = array_values($getorderitems);





                    for ($a = 0; $a < count($getvehiclestocks); $a++) {

                        for ($j = 0; $j < count($getorderitems); $j++) {


                            if ($getvehiclestocks[$a][$a]['product_id'] == $getorderitems[$j][$j]['product_id']) {
                                $total = 0;
                                $total = $getvehiclestocks[$a][$a]['qty'];

                                $cal = $getorderitems[$j][$j]['quantity'];
                                $total = ($total - $cal);
                                $tableupdate =  DB::table('vehicle_stocks')
                                ->where('product_id', $getorderitems[$j][$j]['product_id'])
                                ->update(['qty' => $total]);

                                $updateproductstocks = \DB::select("update product_stocks
                                inner join products on products.material = product_stocks.material
                                set stock = stock + $cal
                                where product_stocks.store_id = $store_id 
                                and products.product_id = $getorderitems[$j][$j]['product_id']");
                            }
                        }
                    }

                    $getdelivery = DeliveryTrip::where('order_id', $order[$i])->get()->toArray();
                    $gettrips = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                        ->get()->toArray();
                    $Updated_Distance = ($gettrips[0]['total_distance']) - ($getdelivery[0]['distance_from_last_point']);
                    $Updated_Time = ($gettrips[0]['total_time']) - ($getdelivery[0]['time_from_last_point']);
                    $updatevalues = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                        ->update(['total_distance' => $Updated_Distance, 'total_time' => $Updated_Time]);




                    if ($requestData['flag'] == 'true') {
                    } else {
                        $dateupdate = DeliveryTrip::where('order_id', $order[$i])
                            ->update(array('deleted_at' =>  $current_date_time));
                    }

                    $updated_status = 14;

                    $orderremove = Order::where('order_id', $order[$i])
                        ->update(array('order_status_id' => $updated_status))
                        && (array('updated_at' =>  $current_date_time));

                    $orderstatusid = DB::table('orders')->where('order_id', $order[$i])
                        ->get()->toArray();
                    $orderlogsdata[] = [

                        'order_id' => $order[$i],
                        'order_status_id' => $orderstatusid[0]->order_status_id,
                        'source_id' => 12,
                        'user_id' => $user->user_id,
                        'created_at' =>  date('Y-m-d H:i:s'),
                        'updated_at' =>  date('Y-m-d H:i:s')


                    ];
                }
                OrderLogs::insert($orderlogsdata);
                $gettrips = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                    ->get()->toArray();
                $getdelivery = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                    ->get()->toArray();
                $batch_distance = $gettrips[0]['total_distance'];
                $batch_gas_cost = round(($gettrips[0]['total_distance'] / 1000) * 10, 2);

                $total_orders = count($getdelivery);
                $balance = 0;

                for ($b = 0; $b < count($getdelivery); $b++) {
                    $bal = \DB::table('orders')->where('order_id', $getdelivery[$b]['order_id'])->value('grand_total');
                    $balance += $bal;
                }



                $batchUpdate = TripBatches::where('batch_no', $gettrips[0]['batch_no'])
                    ->update([
                        "total_orders" => $total_orders,
                        "max_distance" => $batch_distance,
                        "batch_cost" => $balance,
                        "batch_gas_cost" => $batch_gas_cost,
                        "dropped_orders" => count($requestData['removedOrders']),

                    ]);

                $updategascost = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                    ->update(['gas_cost' => $batch_gas_cost]);





                if (count($requestData['addedOrders']) == 0) {
                    $getorders = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                        ->get()->toArray();


                    $sequence = 1;


                    for ($c = 0; $c < count($getorders); $c++) {

                        DeliveryTrip::where('order_id', $getorders[$c]['order_id'])
                            ->update(['sequence' => $sequence]);

                        $sequence++;
                    }




                    try {
                        if ($checktripstatus->trip_status_id == 2) {

                            $trip_id = $requestData['delivery_trip_id'];
                            $source = 1;
                            $getuserid =  Vehicle::where('vehicle_id', $checktripstatus->vehicle_id)->pluck('driver_id');
                            $notification_id =  User::where('user_id', $getuserid[0])->pluck('fcm_token_for_driver_app');
                            $notification_id = $notification_id[0];

                            $title = "Order_Cancelled";
                            $message = "Order Has Been Cancelled Against Trip ID #" . $checktripstatus->trip_code;
                            $id = $getuserid[0];
                            $type = "basic";
                            $res = send_notification_FCM($notification_id, $title, $message, $type, $source);
                        }
                        if ($requestData['flag'] == 'true') {

                            $updated_status = 6;

                            $orderremove = Order::where('order_id', $order[$i])
                                ->update(array('order_status_id' => $updated_status))
                                && (array('updated_at' =>  $current_date_time));
                            $response = [
                                "code" => 200,

                                'message' => 'Order Has Been Cancelled'
                            ];

                            return response()->json($response);
                        }

                        $response = [
                            "code" => 200,

                            'message' => 'Custom trip updated Successfully',
                        ];

                        return response()->json($response);
                    } catch (\Exception $ex) {
                        return response()->json([
                            "code" => 500,
                            "message" => $ex->getMessage()
                        ]);
                    }
                }
            }
            if (isset($requestData['addedOrders']) && count($requestData['addedOrders']) > 0) {






                try {
                    $user = auth('api')->user();
                    $getuserid = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                        ->pluck('created_by')->toArray();
                    $trip = DeliveryTrip::where('delivery_trip_id', $requestData['delivery_trip_id'])->first();

                    if ($trip->trip_startime == null) {
                        //if date and vehicle upates
                        $deliveryUpdates = DeliveryTrip::updateDeliveryTrip($trip, $requestData, $user);
                        //if added orders 
                        $tripMeta = json_encode($trip->toArray(), true);

                        if (isset($requestData['addedOrders']) && count($requestData['addedOrders']) > 0) {
                            $new_orders = ($requestData['addedOrders']);

                            $old_orders = DB::table('deliveries')
                                ->where('delivery_trip_id', $checktripstatus->delivery_trip_id);
                            $old_orders->select('order_id');
                            $old_orders = $old_orders->get()->toArray();
                            $newarray = [];
                            for ($i = 0, $count = count($old_orders); $i < $count; $i++) {
                                $newarray[$i] = $old_orders[$i]->order_id;
                            }


                            $orders = array_merge($new_orders, $newarray);

                            $store = Store::where('store_id', $store_id);
                            $store->select(\DB::raw('stores.map_info'));
                            $storedata = $store->get()->toArray();
                            $getvehicleid = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                                ->get('vehicle_id')->toArray();

                            $deliveries = [];
                            for ($i = 0, $count = count($orders); $i < $count; $i++) {


                                $delivery = [];



                                $orderremove = Order::join('addresses', 'orders.shipping_address_id', '=', 'addresses.address_id');
                                $orderremove->where('orders.order_id', $orders[$i])
                                    ->select(\DB::raw('addresses.map_info'));
                                $orderdata = $orderremove->get()->toArray();



                                $delivery = [
                                    'delivery_trip_id' => $checktripstatus->delivery_trip_id,
                                    'order_id' => $orders[$i],
                                    "vehicle_id" => $getvehicleid[0]['vehicle_id'],
                                    "store_lat" => json_decode($storedata[0]['map_info'], true)['latitude'],
                                    "store_lng" => json_decode($storedata[0]['map_info'], true)['longitude'],
                                    "order_lat" => json_decode($orderdata[0]['map_info'], true)['latitude'],
                                    "order_lng" => json_decode($orderdata[0]['map_info'], true)['longitude']



                                ];


                                array_push($deliveries, $delivery);
                            }
                            $output = _groupby('delivery_trip_id', $deliveries);



                            foreach ($output as $key => $delivery) {



                                $distancedSortedArray[$key] = _reArrangeOrders($delivery, $key);
                                $sequence = 1;
                                $deliveriesUnique = super_unique($distancedSortedArray[$key]['deliveries'], 'order_id');

                                foreach ($deliveriesUnique as $deliveries => &$value) {
                                    $value['sequence'] = $sequence;
                                    $sequence++;
                                }
                                $total_trip_time = 0;
                                $total_trip_distance = 0;
                                $total_service_time = 0;
                                for ($i = 0; $i < count($deliveriesUnique); $i++) {
                                    if ($i == 0) {
                                    } elseif ($deliveriesUnique[$i]['order_lat'] == $prevlat && $deliveriesUnique[$i]['order_lng'] == $prevlon) {

                                        $deliveriesUnique[$i]['time_from_last_point'] = 0;
                                        $deliveriesUnique[$i]['distance_from_last_point'] = 0;
                                    }

                                    $prevlat = $deliveriesUnique[$i]['order_lat'];
                                    $prevlon = $deliveriesUnique[$i]['order_lng'];
                                    $vehicle = DB::table('vehicles')->where('vehicle_id', DeliveryTrip::where('delivery_trip_id', $deliveriesUnique[$i]['delivery_trip_id'])->pluck('vehicle_id'))->first();
                                    $vehicleAvgSpeed = ($vehicle->speed == NULL) ? 60 : json_decode($vehicle->speed, true)['avg'];
                                    $vehicleAvgSpeed = ($vehicleAvgSpeed * 1000) / 60;
                                    $time = ($deliveriesUnique[$i]['distance_from_last_point'] > 0) ? ($deliveriesUnique[$i]['distance_from_last_point'] / $vehicleAvgSpeed) : 0.00;
                                    $finalarray[] = [
                                        "order_id" => $deliveriesUnique[$i]['order_id'],
                                        "delivery_trip_id" => $deliveriesUnique[$i]['delivery_trip_id'],
                                        "service_time" => $deliveriesUnique[$i]['service_time'],
                                        "time_from_last_point" => $deliveriesUnique[$i]['time_from_last_point'],
                                        "distance_from_last_point" => $deliveriesUnique[$i]['distance_from_last_point'],
                                        "sequence" => $deliveriesUnique[$i]['sequence'],

                                    ];
                                    $total_trip_time += round($time, 2);
                                    $total_trip_distance += round($deliveriesUnique[$i]['distance_from_last_point'], 2);
                                    $total_service_time += 15;
                                }
                                $deliveriesUnique = $finalarray;


                                $deliveriesorders = DeliveryTrip::where('delivery_trip_id', $checktripstatus->delivery_trip_id)
                                    ->get()->toArray();
                                $val = 0;
                                for ($i = 0; $i < count($deliveriesUnique); $i++) {

                                    $count = 0;
                                    for ($j = 0; $j < count($deliveriesorders); $j++) {

                                        if ($deliveriesUnique[$i]['order_id'] == $deliveriesorders[$j]['order_id']) {
                                            $val = $val + 1;
                                            $count = 1;
                                            DeliveryTrip::where($deliveriesorders[$j])->update($deliveriesUnique[$i]);
                                        }
                                    }

                                    if ($count == 0) {
                                        $current_id = \DB::select("select delivery_id from deliveries order by delivery_id DESC limit 1");


                                        $converted_number = str_pad($current_id[0]->delivery_id + 1, 0, "0", STR_PAD_LEFT);
                                        $delivery = new DeliveryTrip();
                                        $delivery->delivery_id = +$converted_number;

                                        DeliveryTrip::where('delivery_id', $converted_number)
                                            ->insert($deliveriesUnique[$i]);
                                    }
                                }

                                for ($i = 0; $i < count($deliveriesUnique); $i++) {
                                    $affectedRows = Order::where("order_id", $deliveriesUnique[$i]['order_id'])->update(["order_status_id" => 13]);

                                    $orderstatusid = DB::table('orders')->where('order_id', $deliveriesUnique[$i]['order_id'])
                                        ->get()->toArray();
                                    $orderlogsdata[] = [

                                        'order_id' => $deliveriesUnique[$i]['order_id'],
                                        'order_status_id' => $orderstatusid[0]->order_status_id,
                                        'source_id' => 12,
                                        'user_id' => $user->user_id,
                                        'created_at' =>  date('Y-m-d H:i:s'),
                                        'updated_at' =>  date('Y-m-d H:i:s')


                                    ];
                                }
                                OrderLogs::insert($orderlogsdata);
                                $batch_distance = 0;
                                $batch_gas_cost = 0;
                                $total_orders = 0;
                                $vehicle_counters = 1;
                                $trip_count = 1;
                                $updateDeliveryTrip = DB::table('delivery_trips')->where('delivery_trip_id', $key)
                                    ->update(['gas_cost' => round(($total_trip_distance / 1000) * 10, 2), 'total_distance' => $total_trip_distance, 'total_time' => $total_trip_time, 'service_time' => $total_service_time]);

                                $batch_distance += $total_trip_distance;
                                $batch_gas_cost += round(($total_trip_distance / 1000) * 10, 2);
                                $total_orders += count($deliveriesUnique);
                                $balance = \DB::table('orders')->whereIn('order_id', $orders)->sum('grand_total');
                                $response_array['balance'] = $balance;
                                $batchUpdate = TripBatches::where('batch_no', $batch_no)->update([
                                    "total_orders" => $total_orders,
                                    "no_of_vehicles" => $vehicle_counters,
                                    "max_distance" => $batch_distance,
                                    "batch_cost" => $balance,
                                    "batch_gas_cost" => $batch_gas_cost,

                                ]);
                                $response_array = [
                                    "batch_distance" => $batch_distance,
                                    "batch_gas_cost" => $batch_gas_cost,
                                    "total_orders" => $total_orders,

                                    "total_vehicles" => $vehicle_counters,
                                    "trip_count" => $trip_count,
                                    "balance" => $balance,
                                    "error" => $errors
                                ];


                                $order_ids = [];

                                $orderLogData = [];
                                $orderLogInApp = [];

                                try {

                                    $balance = \DB::table('orders')->whereIn('order_id', $order_ids)->sum('grand_total');

                                    $response_array['balance'] = $balance;
                                } catch (\Exception $ex) {
                                    $err = ["db_error" => $ex->getMessage()];
                                    array_push($error, $err);

                                    return array('code' => 700, 'error' => $err);
                                } catch (\Exception $ex) {




                                    $err = ["db_error" => $ex->getMessage()];
                                    array_push($errors, $err);
                                    return array('code' => 600, 'error' => $error);



                                    $response = [
                                        "code" => 200,


                                        'message' => 'Error In Trip Update',
                                    ];


                                    return response()->json($response);
                                }
                            }

                            $response = [
                                "code" => 200,      'data' => $response_array,

                                'message' => 'Custom trip updated Successfully',
                            ];

                            return response()->json($response);
                        }
                    } elseif ($trip->trip_startime != null && $trip->trip_endtime == null) {
                    } else {
                    }
                } catch (\Exception $ex) {
                    return $ex->getMessage();
                }
            } else {
                echo ('No Order To Update');
            }
        }
    }

    public function removeorders($requestData, $user, $tripstatus)
    {

        $add_order_ids = [];
        $key = DB::table('api_keys')
            ->where("name", 'apipublickey')
            ->pluck('key')->toArray();
        $key = $key[0];
        $headers = array(
            "X-Api-Key:" . $key,
            "Content-Type: application/json"
        );

        $crondata = array(
            'is_approved' => true,
            'order_ids' => $add_order_ids,
            'updated_by' => $user->user_id,
            'trip_id' => $requestData['delivery_trip_id'],
            'flag' => $requestData['flag'],
            'check_status_of_trip' => $tripstatus
        );
        $url = 'http://localhost:8002/update';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($crondata));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
    public function addOrders($requestData, $user, $tripstatus)
    {

        $add_order_ids = $requestData['addedOrders'];
        $key = DB::table('api_keys')
            ->where("name", 'apipublickey')
            ->pluck('key')->toArray();
        $key = $key[0];
        $headers = array(
            "X-Api-Key:" . $key,
            "Content-Type: application/json"
        );

        $crondata = array(
            'is_approved' => true,
            'order_ids' => $add_order_ids,
            'updated_by' => $user->user_id,
            'trip_id' => $requestData['delivery_trip_id'],
            'flag' => $requestData['flag'],
            'check_status_of_trip' => $tripstatus
        );
        $url = 'http://localhost:8002/update';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($crondata));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function getTripsList(Request $request)
    {
        $driver_id = auth()->payload()->get('id');


        $trips = DB::select('select delivery_trips.delivery_trip_id,trip_code,trip_status_title as trip_status,trip_statuses.trip_status_id,
      delivery_trips.trip_date,trip_date as trip_starttime,trip_endtime,customers.customer_id,customers.name as customer_name,customers.mobile as customer_mobile,vehicle_plate_number,users.name as driver_name from vehicles
      inner join delivery_trips on delivery_trips.vehicle_id = vehicles.vehicle_id
      inner join trip_statuses on trip_statuses.trip_status_id = delivery_trips.trip_status_id 
      inner join users on users.user_id = vehicles.driver_id 
      inner join orders on delivery_trips.order_id = orders.order_id
      inner join customers on orders.customer_id = customers.customer_id
        where vehicles.driver_id = ' . $driver_id . ' and delivery_trips.deleted_at is null and delivery_trips.trip_status_id = 4
		order by trip_date desc limit 10');

        if (count($trips) > 0) {
            foreach ($trips as $key => &$value) {
                $value->trip_status = json_decode($value->trip_status, true);
            }

            return response()->json([
                "code" => 200,
                "success" => true,
                "list" => $trips,
            ]);
        } else {
            return response()->json([
                "code" => 404,
                "success" => false,
                "resp" => null,
            ]);
        }
    }

    public function tripInfo(Request $request)
    {
        
        $driver_id = auth()->payload()->get('id');

        $trip_info = DB::select('select orders.order_id,categories.key as order_category,delivery_trips.delivery_trip_id,delivery_trips.aqg_pickup_loc_id,delivery_trips.aqg_dropoff_loc_id,delivery_trips.customer_dropoff_loc_id,orders.order_number as workorder,customer_lots.lot_number,delivery_trips.vehicle_id,delivery_trips.load,delivery_trips.unload,delivery_trips.pickup_check_in,delivery_trips.dropoff_check_in,trip_code,trip_status_title as trip_status,trip_statuses.trip_status_id,orders.customer_id,
        delivery_trips.trip_date,trip_date as trip_starttime,orders.category_id,trip_endtime,start_time_planned,end_time_planned,vehicles.vehicle_id,delivery_trips.pickup_distance,delivery_trips.pickup_time,delivery_trips.dropoff_distance,delivery_trips.dropoff_time,vehicles.driver_id,vehicle_plate_number,current_latitude as vehicle_lat,current_longitude as vehicle_lng from vehicles
        inner join delivery_trips on delivery_trips.vehicle_id = vehicles.vehicle_id and delivery_trips.status = 1
        inner join trip_statuses on trip_statuses.trip_status_id = delivery_trips.trip_status_id and (trip_statuses.key = "ASSIGNED" or trip_statuses.key = "STARTED")
        inner join users on users.user_id = vehicles.driver_id 
        inner join orders on delivery_trips.order_id = orders.order_id
        inner join categories on categories.category_id = orders.category_id
        left join customer_lots on orders.customer_lot_id = customer_lots.customer_lot_id
          where delivery_trips.deleted_at IS NULL
          and trip_statuses.key != "CANCEL"
          and vehicles.driver_id = ' . $driver_id . '
           ORDER BY trip_date ASC');


           $is_pickup_editable = 0;
           $is_dropoff_editable = 0;
           $load_material_from_sap = 0;
           $unload_material_from_sap = 0;


        if (count($trip_info) > 0) {
            for ($i = 0; $i < count($trip_info); $i++) {
                if ($trip_info[$i]->trip_status_id == 2) {
                    $trip_info[0] = $trip_info[$i];
                }
            }
        }
   

        if (count($trip_info) > 0) {
        $category = checkOrderCatgeory($trip_info[0]->order_id);
        $editables = getPickupDropoffEditables($category);
        $is_pickup_editable = $editables[0]->is_pickup_editable;
        $is_dropoff_editable = $editables[0]->is_dropoff_editable;
        $load_material_from_sap = 0;
        $unload_material_from_sap = 0;



            $trip_info[0]->trip_status = json_decode($trip_info[0]->trip_status, true);

            $order = DeliveryTrip::select('order_id')->where('delivery_trip_id', $trip_info[0]->delivery_trip_id)->first();
            if($order == null){
                return response()->json([
                    "code" => 404,
                    "is_pickup_editable"=>0,
                    "is_dropoff_editable"=>0,
                    "load_material_from_sap"=>0,
                    "unload_material_from_sap"=>0,
                    "success" => false,
                    "DeliveryTripInfo" => null,
                    "customer" => null,
                    "pickup" => null,
                    "dropoff" => null,
                    "units" => null,
                    "materials" => null,
                ]);
            }

          
            if($category == "CWA"){
                $load_material_from_sap = 1;
                $unload_material_from_sap = 0;
            }

            if ($order['order_id'] != null) {
                if(isset($trip_info[0]->aqg_pickup_loc_id) && $trip_info[0]->aqg_pickup_loc_id != null){ ##For Asset Request, pickup address is considered as that of delivery_trips table
                    $order_address = $pickup_address = Store::select('latitude', 'longitude', 'address')->where('store_id', $trip_info[0]->aqg_pickup_loc_id)->first();
                    $order_address['pickup_address_id'] = $trip_info[0]->aqg_pickup_loc_id; //To satisfy further checks
                }
                else{                                                                                    ##For Normal Orders, with pickup address stored in orders table
                    $order_address = Order::select('pickup_address_id', 'aqg_dropoff_loc_id', 'customer_dropoff_loc_id')->where('order_id', $order['order_id'])->first();
                }
                if ($order_address) {

                    //Get Pickup Address Against Order
                    if ($order_address['pickup_address_id'] != null) {
                        if(!isset($pickup_address) || $pickup_address == null){
                            $pickup_address = Address::select('latitude', 'longitude', 'address')->where('address_id', $order_address['pickup_address_id'])->first();
                        }
                        
                        if ($pickup_address != null) {
                            if ($pickup_address['latitude'] == null || $pickup_address['longitude'] == null) {
                                return response()->json([
                                    "code" => 204,
                                    "success" => false,
                                    'message' => 'Latitude Longitude not found against Pickup Address ID # ' . $order_address['pickup_address_id'],
                                ]);
                            }
                        } else {
                            return response()->json([
                                "code" => 204,
                                "success" => false,
                                'message' => 'Pickup address not found against Pickup Address ID # ' . $order_address['pickup_address_id'],
                            ]);
                        }
                    } else {
                        return response()->json([
                            "code" => 204,
                            "success" => false,
                            'message' => 'Pickup Location Does Not Exist For Order ID # ' . $order['order_id'],
                        ]);
                    }

                    //Get Delivery Addresses Against Orders      
                    if ($trip_info[0]->customer_dropoff_loc_id != null) {
                        $dropoff_address = Address::select('address', 'latitude', 'longitude')->where('address_id', $trip_info[0]->customer_dropoff_loc_id)->first();

                        if ($dropoff_address != null) {
                            if ($dropoff_address['latitude'] == null || $dropoff_address['longitude'] == null) {
                                return response()->json([
                                    "code" => 204,
                                    "success" => false,
                                    'message' => 'Latitude Longitude not found against Customer Dropoff Address ID # ' . $trip_info[0]->customer_dropoff_loc_id,
                                ]);
                            }
                        } else {
                            return response()->json([
                                "code" => 204,
                                "success" => false,
                                'message' => 'Dropoff Address not found against Customer Dropoff Address ID # ' . $trip_info[0]->customer_dropoff_loc_id,
                            ]);
                        }
                    } elseif ($trip_info[0]->aqg_dropoff_loc_id != null) {
                        $dropoff_address = Store::select('address', 'latitude', 'longitude', 'mobile', 'whatsapp')->where('store_id', $trip_info[0]->aqg_dropoff_loc_id)->first();
                        if ($dropoff_address != null) {
                            if ($dropoff_address['latitude'] == null || $dropoff_address['longitude'] == null) {
                                return response()->json([
                                    "code" => 204,
                                    "success" => false,
                                    'message' => 'Latitude Longitude not found against AQG Dropoff Address ID # ' . $trip_info[0]->aqg_dropoff_loc_id,
                                ]);
                            }
                        } else {
                            return response()->json([
                                "code" => 204,
                                "success" => false,
                                'message' => 'Dropoff Address not found against AQG Dropoff Address ID # ' . $trip_info[0]->aqg_dropoff_loc_id,
                            ]);
                        }
                    } else {
                        return response()->json([
                            "code" => 204,
                            "success" => false,
                            'message' => 'Dropoff Location Does Not Exist For Trip ID # ' . $trip_info[0]->delivery_trip_id,
                        ]);
                    }
                } else {
                    return response()->json([
                        "code" => 204,
                        "success" => false,
                        'message' => 'Order not found',
                    ]);
                }
            } else {
                return response()->json([
                    "code" => 204,
                    "success" => false,
                    'message' => 'Order ID not found for Delivery Trip',
                ]);
            }
               

            $delivery_trip_id = $trip_info[0]->delivery_trip_id;
           

            // get vehicle lat lng for start constraint
            if ($trip_info[0]->trip_status_id == 2) {
                $vehicle_info = DeliveryTrip::select('start_latitude', 'start_longitude')->where('delivery_trip_id', $delivery_trip_id)->first();
                $start_latitude = $vehicle_info['start_latitude'];
                $start_longitude = $vehicle_info['start_longitude'];
            } else {
                $vehicle_id = $trip_info[0]->vehicle_id;
                $vehicle_info = Vehicle::select('current_latitude', 'current_longitude')->where('vehicle_id', $vehicle_id)->first();
                $start_latitude = $vehicle_info['current_latitude'];
                $start_longitude = $vehicle_info['current_longitude'];
            }
             //Constraints For Driver 
             $pickconstraints=[];
             $dropconstraints=[];
            $constraint=Constraints::join('locations','constraints.location_id','=','locations.location_id')
            ->where('trip_id',$delivery_trip_id)
            ->where('constraints.location_level_id',2)
            ->get(['key','trip_id','locations.delay','location_name','latitude','longitude'])->toArray();
             for($i=0;$i<count($constraint);$i++)
             {
               if($constraint[$i]['key']=="PICKUP"){
           
                $pickupconstraint['delivery_trip_id'] = $constraint[$i]['trip_id'];
                $pickupconstraint['key'] = $constraint[$i]['key'];
                $pickupconstraint['delay'] = $constraint[$i]['delay'];
                $pickupconstraint['name'] = json_decode($constraint[$i]['location_name']) ;
                $pickupconstraint['latitude'] = $constraint[$i]['latitude'] ;
                $pickupconstraint['longitude'] = $constraint[$i]['longitude'] ;

                array_push($pickconstraints, $pickupconstraint);

               }
       
               else if ($constraint[$i]['key']=="DROPOFF") {
                $dropoffconstraint['delivery_trip_id'] = $constraint[$i]['trip_id'];
                $dropoffconstraint['key'] = $constraint[$i]['key'];
                $dropoffconstraint['delay'] = $constraint[$i]['delay'];
                $dropoffconstraint['name'] = json_decode($constraint[$i]['location_name']) ;
                $dropoffconstraint['latitude'] = $constraint[$i]['latitude'] ;
                $dropoffconstraint['longitude'] = $constraint[$i]['longitude'] ;

                array_push($dropconstraints, $dropoffconstraint);

               }


             }
         

            // get pickup and dropoff time
            $trip_time = DeliveryTrip::select('pickup_time', 'dropoff_time', 'start_time_planned', 'pickup_service_time', 'dropoff_service_time')->where('delivery_trip_id', $delivery_trip_id)->first();

            // Customer Info
            $customer = Customer::select('name', 'mobile', 'whatsapp')->where('customer_id', $trip_info[0]->customer_id)->first();
            $customer['address'] = $dropoff_address['address'];

            if($category == "CWA"){
                $customer = Address::select('address as name',DB::raw('null as mobile,null as whatsapp,address as address')) 
                                ->where('address_id',$trip_info[0]->customer_dropoff_loc_id)->first();
            }

            $corporate_customer_material = \App\Model\CorporateCustomerMaterial::where('status',1)
                                    ->get(['id','corporate_cust_address_id','parent_material_id','child_material_code','child_material_desc'])
                                    ->toArray();
            $customer_addresses = \App\Model\Address::where('customer_id',$trip_info[0]->customer_id)
            ->where('status',1)
            ->whereHas('type', function($q){
                $q->where('key', '=', 'CORPORATE CUSTOMER');
            })
            ->get(['address_id','address','latitude','longitude','erp_id','address_title']);
            $cancel_reasons = \App\Model\CancelReason::where('status',1)
                                ->where('erp_id','!=',null)
                                ->get(['cancel_reason_id','reason','erp_id']);
            foreach($cancel_reasons as $reason){
                $reason->reason = json_decode($reason->reason);
            }
            // Order Material PICKUP MATERIAL
        
            if ($trip_info[0]->load != null) {
               

                $order_materials = DB::select('select pickup_materials.material_id,unit,weight,material.name,material.material_code from pickup_materials 
                inner join material on pickup_materials.material_id = material.material_id
                where pickup_materials.trip_id = ' .  $trip_info[0]->delivery_trip_id);

                if($category == "SKIP_COLLECTION"){

                    $order_materials = DB::select('select pickup_materials.material_id,unit,weight,material.name,material.material_code,pickup_materials.skip_id, ia.title as skip_title from pickup_materials 
                    inner join material on pickup_materials.material_id = material.material_id
                    inner join skips s on pickup_materials.skip_id = s.skip_id 
                    inner join inv_assets ia on ia.asset_id = s.asset_id 
                    where pickup_materials.trip_id = ' .  $trip_info[0]->delivery_trip_id);
                }
                elseif($category == "ASSET"){ //27-10-2022

                    $order_materials = DB::select('select pickup_materials.material_id,pickup_materials.asset_id as skip_id,unit,weight,material.name,material.material_code, ia.title as skip_title from pickup_materials 
                    inner join material on pickup_materials.material_id = material.material_id
                    inner join inv_assets ia on pickup_materials.asset_id = ia.asset_id 
                    where pickup_materials.trip_id = ' .  $trip_info[0]->delivery_trip_id);
                }
                elseif($category == "CWA"){ //23-12-2022

                    // $corporate_customer_id = \App\Model\Address::where('address_id',$trip_info[0]->customer_dropoff_loc_id)->value('a_id');
                    // $corporate_customer_id = $corporate_customer_id != null ? $corporate_customer_id : null;
                    $order_materials = DB::select('select corporate_customer_material.id as material_id,corporate_customer_material.parent_material_id,unit,weight,corporate_customer_material.child_material_desc as name,corporate_customer_material.child_material_code as material_code from pickup_materials 
                    inner join material on pickup_materials.material_id = material.material_id
                    inner join corporate_customer_material on pickup_materials.material_id = corporate_customer_material.parent_material_id
                    where pickup_materials.trip_id = ' .  $trip_info[0]->delivery_trip_id .'
                    AND corporate_customer_material.corporate_cust_address_id = ' . $trip_info[0]->customer_dropoff_loc_id);
                    // return $order_materials;
                }
            } else { //TRIP ASSIGNED MATERIAL
                

              $delivery_TripID=$trip_info[0]->delivery_trip_id;
              $order_ID= $trip_info[0]->order_id;
              $customer_id = Order::where('order_id',$order_ID)->value('customer_id');

                if($category == "SKIP_COLLECTION"){
                    
                    $skips = \DB::select("select DISTINCT skips.skip_id, title from skips
                    inner join inv_assets ia on skips.asset_id = ia.asset_id  
                    where skips.customer_id = $customer_id
                    and skips.deleted_at IS NULL");
                    $materials= \DB::select ("select DISTINCT tam.material_id as tam_material_id,tam.unit as tam_unit
                    ,tam.weight as tam_weight,m2.material_code,m2.name, s.skip_id, ia.title,
                    om.unit as om_unit,om.weight as om_weight
                    from trip_assigned_materials tam 
                    inner join material m2 on tam.material_id = m2.material_id 
                    inner join order_material om on m2.material_id = om.material_id
                    inner join skips s on tam.skip_id = s.skip_id 
                    inner join inv_assets ia on ia.asset_id = s.asset_id 
                    where tam.delivery_trip_id = $delivery_TripID
                    and s.customer_id = $customer_id
                    and om.order_id = $order_ID") ;
                }
                elseif($category == "ASSET"){

                    $pickup_id = $trip_info[0]->aqg_pickup_loc_id;
                    $assigned_to = getAssetTransactionSource('YARD');
                    $skips = \DB::select("select DISTINCT asset_id as skip_id, title from inv_assets
                    where assigned_to = $assigned_to
                    and assignee_id = $pickup_id
                    and deleted_at IS NULL");
                    $materials= \DB::select ("select DISTINCT tam.asset_id as skip_id,ia.title,tam.material_id as tam_material_id, m.name as name, tam.unit as tam_unit,tam.weight as tam_weight, null as om_unit, null as om_weight, m.material_code
                    from trip_assigned_materials tam 
                    inner join material m on m.material_id = tam.material_id 
                    inner join inv_assets ia on tam.asset_id = ia.asset_id 
                    where tam.delivery_trip_id = $delivery_TripID") ;
                }
                elseif($category == "CWA"){

                    $materials = DB::select('select corporate_customer_material.id as tam_material_id,corporate_customer_material.parent_material_id,unit as tam_unit,weight as tam_weight,corporate_customer_material.child_material_desc as name,corporate_customer_material.child_material_code as material_code from trip_assigned_materials 
                    inner join material on trip_assigned_materials.material_id = material.material_id
                    inner join corporate_customer_material on trip_assigned_materials.material_id = corporate_customer_material.parent_material_id
                    where trip_assigned_materials.delivery_trip_id = ' .  $trip_info[0]->delivery_trip_id .'
                    AND corporate_customer_material.corporate_cust_address_id = ' . $trip_info[0]->customer_dropoff_loc_id);
                   
                  
                }
                else{
                    $materials= \DB::select ("select DISTINCT tam.material_id as tam_material_id,null as tam_unit
                    ,0 as tam_weight,m2.material_code,m2.name,
                    null as om_unit,0 as om_weight
                    from trip_assigned_materials tam 
                    inner join material m2 on tam.material_id = m2.material_id 
                    where tam.delivery_trip_id = $delivery_TripID
                    ") ;
                }
               
                if(count($materials)>0)
                {
                    $order_materials=[];
                    foreach ($materials as $key => &$item) {
                        // return $materials;
                        $item->name = gettype($item->name) == "object" || gettype($item->name) == "array" ? $item->name : json_decode($item->name, true); 
                       
                       
                         $order_materials[$key] = [
                            "material_id" => $item->tam_material_id,
                            "unit" => isset($item->tam_unit)?$item->tam_unit:$item->om_unit,   
                            "weight" => isset($item->tam_weight)?$item->tam_weight:$item->om_weight,
                            "name" => $item->name,
                            "material_code"=>$item->material_code,
                            "parent_material_id" => isset($item->parent_material_id)?$item->parent_material_id : null,
                            "skip_id" => isset($item->skip_id)?$item->skip_id : null,
                            "skip_title" => isset($item->title) ? $item->title : null
                                   
                        ];            
                    }
                   
                    $order_materials=json_decode(json_encode($order_materials));

                }
                else {
                    
                    
                    
                    if($category == "SKIP_COLLECTION"){

                        $order_materials = DB::select('select DISTINCT material.material_id,unit,osr.skip_id,0 as weight,material.name, s.skip_id,ia.title as skip_title,material.material_code from orders 
                        inner join order_service_requests osr on osr.order_id = orders.order_id
                        left join skips s on osr.skip_id = s.skip_id
                        left join material on s.material_id = material.material_id
                        left join inv_assets ia on s.asset_id = ia.asset_id
                        where s.customer_id = '.$customer_id."
                        AND osr.order_id = ".$trip_info[0]->order_id
                
                );
                    }
                    else{

                        #Set weight as zero and unit as null
                        $order_materials = DB::select('select order_material.material_id,null as unit,0 as weight,material.name,material.material_code from order_material 
                    inner join material on order_material.material_id = material.material_id
                    where order_material.order_id = ' .  $trip_info[0]->order_id);
                    }
                    

                }
  

            }
            if (count($order_materials) > 0) {

                foreach ($order_materials as $key => &$value) {


                    $value->name = gettype($value->name) == "object" ? $value->name : json_decode($value->name, true);
                         
                    $value->om_unit = isset($value->om_unit) ? $value->om_unit : null;
                    $value->om_weight = isset($value->om_weight) ? $value->om_weight : null;
                     $order_materials[$key] = [
                        "material_id" => $value->material_id,
                        "unit" => isset($value->unit)?$value->unit:$value->om_unit,   
                        "weight" => isset($value->weight)?$value->weight:$value->om_weight,
                        "name" => $value->name,
                        "material_code"=>$value->material_code,
                        "skip_id" => isset($value->skip_id)?$value->skip_id : null,
                        "skip_title" => isset($value->skip_title)?$value->skip_title : null,
                        "parent_material_id" => isset($value->parent_material_id)?$value->parent_material_id : null,
                        "asset_id" => isset($value->asset_id)?$value->asset_id : null,
                        "asset_title" => isset($value->asset_title) ? $value->asset_title : null // 27-10-2022
                               
                    ];

                }
            }
            
            // All Units Info
            $units = Unit::select('id', 'unit')->where('status', 1)->get()->toArray();
            if (count($units) > 0) {
                foreach ($units as $key => &$value) {
                    $value['unit'] = json_decode($value['unit'], true);
                }
            } else {
                return response()->json([
                    "code" => 204,
                    "success" => false,
                    'message' => 'Units not found',
                ]);
            }
          

            // All Material Info
            

            if($category == "CWA"){
                $materials = \App\Model\CorporateCustomerMaterial::where('status',1)
                                ->select('id as material_id','corporate_cust_address_id','child_material_code as material_code',
                                'child_material_desc as name','parent_material_id',DB::raw('null as default_unit'))
                                ->get()->toArray();
            } else{
                $materials = Material::where('customer_id',$trip_info[0]->customer_id)->get(['material_id', 'name', 'material_code','default_unit'])->toArray();
            }
            if (count($materials) > 0) {
                foreach ($materials as $key => &$value) {
                    $value['name'] = json_decode($value['name'], true);
                }
            } else {
                return response()->json([
                    "code" => 204,
                    "success" => false,
                    'message' => 'Materials not found',
                ]);
            }
            
            return response()->json([
                "code" => 200,
                "success" => true,
                "is_dropoff_editable"=>$is_dropoff_editable,
                "is_pickup_editable"=>$is_pickup_editable,
                "load_material_from_sap"=>$load_material_from_sap,
                "unload_material_from_sap"=>$unload_material_from_sap,
                "DeliveryTripInfo" => $trip_info[0],
                "customer" => $customer,
                "skips" => isset($skips) ? $skips : null,
                // "assets" => isset($assets) ? $assets : null,
                "constraints_list" => [
                    [
                        "name" => "Start",
                        "latitude" => $start_latitude,
                        "longitude" => $start_longitude,
                        "time_arrival" => $trip_time['start_time_planned']
                    ],
                    [
                       
                 
                        "pickupconstraints"=> $pickconstraints

                    ],


                    [
                        "name" => "Pickup",
                        "latitude" => $pickup_address['latitude'],
                        "longitude" => $pickup_address['longitude'],
                        "time_arrival" => $trip_time['pickup_time'],
                        "pickup_service_time" => $trip_time['pickup_service_time'],
                        "address" => $pickup_address['address']
                        
                    ],

                    [
                        "dropoffconstraints"=> $dropconstraints

                    ],
              

             
                    [
                        "name" => "Dropoff",
                        "latitude" => $dropoff_address['latitude'],
                        "longitude" => $dropoff_address['longitude'],
                        "time_arrival" => $trip_time['dropoff_time'],
                        "dropoff_service_time" => $trip_time['dropoff_service_time'],
                        "mobile" => isset($dropoff_address['mobile']) ? $dropoff_address['mobile'] : null,
                        "whatsapp" => isset($dropoff_address['whatsapp']) ? $dropoff_address['whatsapp'] : null,
                        "address" => $dropoff_address['address'],
                    ],

                   
                ],
                "order_materials" => $order_materials,
                "units" => $units,
                "materials" => $materials,
                "customer_addresses"=>isset($customer_addresses) ? $customer_addresses : null,
                // "corporate_customer_material"=>isset($corporate_customer_material) ? $corporate_customer_material : null,
                "cancel_reasons"=>isset($cancel_reasons) ? $cancel_reasons : null,

            ]);
        } else {
            return response()->json([
                "code" => 404,
                "is_pickup_editable"=>0,
                "is_dropoff_editable"=>0,
                "load_material_from_sap"=>0,
                "unload_material_from_sap"=>0,
                "success" => false,
                "DeliveryTripInfo" => null,
                "customer" => null,
                "pickup" => null,
                "dropoff" => null,
                "units" => null,
                "materials" => null,
            ]);
        }
    }

    public function startTrip(Request $request)
    {
        $data =  json_decode($request->getContent(), true);
        $driver_id = auth()->payload()->get('id');

        //    Fields Validation      
        $rules = [
            'delivery_trip_id' => 'required',
            // 'latitude' => 'required',
            // 'longitude' => 'required'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        if(!isset($data['lat']) || !isset($data['lng']))
        {
            $data['lat']=null;
             $data['lng']=null;

        }
  

        if (isset($data['delivery_trip_id']) && $data['delivery_trip_id'] != null) {

            $driver_trips = Vehicle::select('vehicle_id')->with('delivery_trips:vehicle_id,trip_status_id')->where('driver_id', $driver_id)->get()->toArray();
            $driver_trip = $driver_trips[0]['delivery_trips'];

            foreach ($driver_trip as $trip) {
                if ($trip['trip_status_id'] == 2) {
                    return response()->json([
                        "code" => 403,
                        "success" => false,
                        "resp" => 'Already started trip available, close other trips first',
                    ]);
                }
            }

            $trip_data = DB::select('select vehicles.driver_id,delivery_trips.customer_dropoff_loc_id,vehicles.erp_id,users.first_name,users.erp_id as user_name,users.last_name,users.mobile,vehicles.vehicle_plate_number,delivery_trips.vehicle_id,delivery_trips.order_id,delivery_trips.trip_status_id,trip_statuses.key, delivery_trips.order_id from delivery_trips
                inner join trip_statuses on trip_statuses.trip_status_id = delivery_trips.trip_status_id
                inner join vehicles on vehicles.vehicle_id = delivery_trips.vehicle_id
                inner join users on vehicles.driver_id = users.user_id
                where delivery_trips.delivery_trip_id = ' . $data['delivery_trip_id']);
                // return $trip_data[0]->erp_id;
                
            if (count($trip_data) > 0) {

                if (isset($trip_data[0]->order_id) && $trip_data[0]->order_id != '') {
                    $order_id = $trip_data[0]->order_id;
                } else {
                    return response()->json([
                        "code" => 403,
                        "success" => false,
                        "resp" => 'Order ID is missing',
                    ]);
                }


               
                // prepare data fro SAP
                $kunnr_id = \App\Model\Address::where('address_id',$trip_data[0]->customer_dropoff_loc_id)->value('erp_id');
                $vehicle_id = $trip_data[0]->vehicle_id;
                $order_id = $trip_data[0]->order_id;
                $current_Lat=$data['lat'];
                $current_Lng=$data['lng'];
                $check_category = checkOrderCatgeory($order_id);

                if($check_category == "CWA"){

                    $SAP_data['VehicleId'] = $trip_data[0]->erp_id;
                    $SAP_data['VehicleNumber'] = $trip_data[0]->vehicle_plate_number;
                    $SAP_data['Username'] = $trip_data[0]->user_name;
                    $SAP_data['DriverName'] = $trip_data[0]->first_name." ".$trip_data[0]->last_name;
                    $SAP_data['DriverContact'] = $trip_data[0]->mobile != null ? $trip_data[0]->mobile : "";
                    $SAP_data['Kunnr'] = $kunnr_id;
                   
                    $SAP_data['MaterialidParent'] = "";
                    $rules = [
                        'VehicleId' => 'required|exists:vehicles,erp_id',
                        'DriverName' => 'required',
                        'Username' => 'required',
                        'Kunnr' => 'required'   
                    ];
        
                    $validator = Validator::make($SAP_data, $rules);
                    if ($validator->fails()) {  
                        return responseValidationError('Fields Validation Failed.', $validator->errors());
                    }
                
                $SAP_data['AqgnowRef'] = (string)$data['delivery_trip_id']; // For CWA order category
                // $sap_data = [
                //     'body' => $SAP_data
                // ];
                $data_sap = json_encode($SAP_data);

                $sap_obj = new SapApi();
                $sap_obj->fname = "startTrip";
                $sap_obj->body = $data_sap;
                $sap_obj->save();
                
                $response = DeliveryTrip::sendTripDataToSAP($SAP_data,$sap_obj);
               
            }
                $update_trip = DeliveryTrip::where('delivery_trip_id', $data['delivery_trip_id'])->update(['trip_status_id' => 2, 'trip_startime' => date('Y-m-d H:i:s'), 'start_latitude' => $current_Lat, 'start_longitude' => $current_Lng]);

                if ($update_trip) {
                    // Log Trip
                    $logdata[] = [
                        'trip_id' => $data['delivery_trip_id'],
                        'order_id' => $order_id,
                        'trip_status_id' =>  2,
                        'created_at' =>  date('Y-m-d H:i:s')
                    ];
                    TripLogs::insert($logdata);
                    Vehicle::where('vehicle_id', $vehicle_id)->update(['job_order' => $order_id]);

                    
                   

                    return response()->json([
                        "code" => 200,
                        "success" => true,
                        "resp" => 'Trip started successfully',
                    ]);
                } else {
                    return response()->json([
                        "code" => 403,
                        "success" => false,
                        "resp" => 'Something went wrong'
                    ]);
                }
            } else {
                return response()->json([
                    "code" => 403,
                    "success" => false,
                    "resp" => 'Delivery trip not found against delivery trip id ' . $data['delivery_trip_id'],
                ]);
            }
        } else {
            return response()->json([
                "code" => 403,
                "success" => false,
                "resp" => 'Delivery trip id not found',
            ]);
        }
    }

    public function endTrip(Request $request)
    {


        return response()->json([
            "code" => 200,
            "success" => true,
            "resp" => 'Delivery trip ended succesfully'
        ]);


        // $data =  json_decode($request->getContent(), true);

        // //    Fields Validation      
        // $rules = [
        //     'delivery_trip_id' => 'required|string',
        //     // 'latitude' => 'required',
        //     // 'longitude' => 'required'
            
        // ];
        // $validator = Validator::make($data, $rules);

        // if ($validator->fails()) {
        //     return responseValidationError('Fields Validation Failed.', $validator->errors());
        // }
      

        // $delivery_trip =  DB::select('select vehicles.driver_id,delivery_trips.order_id,trip_startime,start_latitude,start_longitude,delivery_trips.vehicle_id,pickup_check_in,dropoff_check_in,unload,delivery_trips.load from delivery_trips
        // inner join vehicles on vehicles.vehicle_id = delivery_trips.vehicle_id
        // where delivery_trips.delivery_trip_id = ' . $data['delivery_trip_id']);

        // if(!isset($data['lat']) || !isset($data['lng']))
        // {
        //     $data['lat']=null;
        //      $data['lng']=null;

        // }
     
        // //Calculate Total Distance

        // $lat1 = $delivery_trip[0]->start_latitude;
        // $lng1 = $delivery_trip[0]->start_longitude;

        // $vehicleid = $delivery_trip[0]->vehicle_id;
        // $lat2 = $data['lat'];
        // $lng2 = $data['lng'];

        // if($lat1=NULL || $lng1=NULL || $lat2=NULL || $lng2=NULL)
        // {
        //     $total_distance=0;
        // }
        // else
        // {
        //     $total_distance = DeliveryTrip::distance($lat1,$lng1,$lat2,$lng2);
        // }
        // $total_distance = DeliveryTrip::distance($lat1,$lng1,$lat2,$lng2);

        // //Calculate Total Time

        // $endtime = date('Y-m-d H:i:s');

        // $starttime = $delivery_trip[0]->trip_startime;

        // $to = Carbon::createFromFormat('Y-m-d H:i:s', '' . $starttime);
        // $from = Carbon::createFromFormat('Y-m-d H:i:s', '' . $endtime);

        // $diff_in_minutes = $to->diffInMinutes($from);
        // $hours = floor($diff_in_minutes / 60);
        // $min = $diff_in_minutes - ($hours * 60);
        // $totaltime = $hours.":".$min;
        // // $totaltime = gmdate("H:i", ($diff_in_minutes * 60));

        // if (count($delivery_trip) > 0) {

        //     if (isset($delivery_trip[0]->order_id) && $delivery_trip[0]->order_id != '') {
        //         $order_id = $delivery_trip[0]->order_id;
        //     } else {
        //         return response()->json([
        //             "code" => 403,
        //             "success" => false,
        //             "resp" => 'Order ID is missing',
        //         ]);
        //     }

        //     $update_delivery_trip = DeliveryTrip::where('delivery_trip_id', $data['delivery_trip_id'])
        //     ->update(['trip_status_id' => 4, 'trip_endtime' => date('Y-m-d H:i:s'),'actual_time'=>$totaltime,
        //     'actual_distance'=>$total_distance,'dropoff_latitude' => $data['lat'],'dropoff_longitude' => $data['lng']]);

        //     if ($update_delivery_trip) {



        //         // Log Trip
        //         $logdata[] = [
        //             'trip_id' => $data['delivery_trip_id'],
        //             'order_id' => $order_id,
        //             'trip_status_id' =>  4,
        //             'created_at' =>  date('Y-m-d H:i:s')
        //         ];
        //         TripLogs::insert($logdata);

        //         return response()->json([
        //             "code" => 200,
        //             "success" => true,
        //             "resp" => 'Delivery trip ended succesfully'
        //         ]);
        //     } else {
        //         return response()->json([
        //             "code" => 403,
        //             "success" => false,
        //             "resp" => 'Something went wrong'
        //         ]);
        //     }
        // } else {
        //     return response()->json([
        //         "code" => 403,
        //         "success" => false,
        //         "resp" => 'Delivery trip not found against delivery trip id ' . $data['delivery_trip_id']
        //     ]);
        // }


       
    }

    public function saveVehicleLocation(Request $request)
    {
        $data =  json_decode($request->getContent(),false);
        $data1 =  json_decode($request->getContent(),true);

        // //    Fields Validation      
            $rules = [
                'delivery_trip_id' => 'required',
                'vehLoc' => 'required'
            ];

            $validator = Validator::make($data1, $rules);

            if ($validator->fails()) {
                return responseValidationError('Fields Validation Failed.', $validator->errors());
            }

            $getID=DB::table('vehicle_locations')->where('delivery_trip_id',$data->delivery_trip_id)
            ->get(['vehLoc'])->toArray();





           
        $vehLoc=json_encode($data->vehLoc,JSON_UNESCAPED_SLASHES);
      

   




    
            if(count($getID)>0)

            {
 $getID=$getID[0]->vehLoc;
$getID=json_decode($getID);
$new=$data->vehLoc;
$full=array_merge($getID,$new);
$full=json_encode($full,JSON_UNESCAPED_SLASHES);
              


                $vehicle=DB::table('vehicle_locations')->where('delivery_trip_id',$data->delivery_trip_id)
                ->update([
                    'vehLoc' => $full,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                
               
            }
            else {
                $vehicle=DB::table('vehicle_locations')->insert([
                    'delivery_trip_id'=>$data->delivery_trip_id,
                    'vehLoc' => $vehLoc,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

            }

        return response()->json([
            "code" => 200,
            "success" => true,
            "resp" => 'Location Successfully Updated'
        ]);

        
    }

    public function getTripData(Request $request,$delivery_trip_id)
    {

        // //    Fields Validation      
        $validator = Validator::make(['delivery_trip_id' => $delivery_trip_id], [
            'delivery_trip_id' => 'required|integer|min:1|exists:delivery_trips,delivery_trip_id',
        ]);

        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
            $get_data=DB::table('vehicle_locations')
            ->Join('delivery_trips','vehicle_locations.delivery_trip_id','=','delivery_trips.delivery_trip_id')
            ->Join('vehicles','delivery_trips.vehicle_id','=','vehicles.vehicle_id')
            ->where('vehicle_locations.delivery_trip_id',$delivery_trip_id)->get(['vehLoc','vehicles.current_speed']);
            // return $getData;
if(count($get_data) > 0)
{
$getData=json_decode($get_data[0]->vehLoc);
foreach($getData as $get_data1){
    $get_data1->current_speed = $get_data[0]->current_speed;
}
}else{
    $getData = $get_data;
}


$order_id = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->value('order_id');
if(checkOrderCatgeory($order_id) == "ASSET"){
    $getOrderID=DeliveryTrip::with('order','store')->where('delivery_trip_id',$delivery_trip_id)->first()->toArray();
    $getOrderID['order']['address'] = $getOrderID['store'];
    
}else{
    $getOrderID=DeliveryTrip::with('order','order.address')->where('delivery_trip_id',$delivery_trip_id)->first()->toArray();
}
if(!isset($getOrderID['order']) || $getOrderID['order'] == null || !isset($getOrderID['order']['address']) || $getOrderID['order']['address'] == null){
    return response()->json([
        "code" => 204,
        'message' => 'Pickup Location Does Not Exist For Trip ID # ' . $delivery_trip_id,

    ]);
}
$pickup_address=[
    "latitude" => $getOrderID['order']['address']['latitude'],
    "longitude" => $getOrderID['order']['address']['longitude'],
    "pickup_location" => $getOrderID['order']['address']['address'],

];

$getStart=DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->get(['start_latitude','start_longitude','customer_dropoff_loc_id','aqg_dropoff_loc_id'])->toArray();
if(isset($getStart) && count($getStart) > 0)
{

 $start_Address=[
     "latitude" => $getStart[0]['start_latitude'],
     "longitude" => $getStart[0]['start_longitude']
     

 ];   
}

   //Get Delivery Addresses Against Orders 
   if(isset($getStart[0]['customer_dropoff_loc_id']))
   {
 
     $sendaddressid=$getStart[0]['customer_dropoff_loc_id'];
     $address = getAddress($sendaddressid);

   }
   else if (isset($getStart[0]['aqg_dropoff_loc_id']))
             {
     $sendaddressid=$getStart[0]['aqg_dropoff_loc_id'];
     $address = getAQGAddress($sendaddressid);

 
   }



   else {
     return response()->json([
         "code" => 204,
         'message' => 'Dropoff Location Does Not Exist For Trip ID # ' . $delivery_trip_id,

     ]);
   }

   //Check if api hit is from oms side
   $dropoff_address=[
    "latitude" => $address[0]->latitude,
    "longitude" => $address[0]->longitude,
    "drop_off_location"=>$address[0]->address_title,

];
        


          //Vehicle Icon
            $getIcon=DB::table('delivery_trips')
            ->join('vehicles','delivery_trips.vehicle_id','=','vehicles.vehicle_id')
            ->join('vehicle_types','vehicles.vehicle_type_id','=','vehicle_types.vehicle_type_id')
            ->where('delivery_trip_id',$delivery_trip_id)
            ->get(['vehicle_types.icon','delivery_trips.vehicle_id','vehicles.vehicle_plate_number'])->first();
            
            
            $category_key = Order::with('category:category_id,key')->where('order_id',$getOrderID['order_id'])->first();
            $customer_id = $category_key['customer_id'];
           
            if(Auth::guard('oms')->check() && isset($category_key['category']['key']) && $category_key['category']['key'] == "PICKUP"){
                $dropoff_address=[
                            "latitude" => null,
                            "longitude" => null,
                        ];
                        
                $getData = json_decode(json_encode($getData),true);
                foreach($getData as $i => &$get_data){
                    $date1 = Carbon::createFromFormat('Y-m-d H:i:s', $get_data['time']);
                    $date2 = Carbon::createFromFormat('Y-m-d H:i:s', $getOrderID['pickup_time']);
                    $result = $date1->lte($date2);

                    if($result == false){
                        unset($getData[$i]);
                    }

                   }
            }

            if(isset($customer_id) && $customer_id != null)
            $material = \App\Model\Material::where('customer_id',$customer_id)
                                            ->get(['material_id','erp_id','customer_id','material_code','name'])
                                            ->toArray();

            return response()->json([
                "code" => 200,
                "success" => true,
                "data" => $getData,
                "material"=> isset($material) ? $material : null,
                "start_address"=> $start_Address,
                "pickup_address"=> $pickup_address,
                "dropoff_address" => $dropoff_address,
                "icon"=> isset($getIcon->icon)?$getIcon->icon:null,
                "vehicle_id"=> isset($getIcon->vehicle_id)?$getIcon->vehicle_id:null,
                "vehicle_plate_number"=> isset($getIcon->vehicle_plate_number)?$getIcon->vehicle_plate_number:null,
                "resp" => 'Location Successfully Updated'
            ]);
    }
    public function checkIn(Request $request) {
        $data = json_decode($request->getContent(),true);
        
        //    Fields Validation      
        $rules = [
          'delivery_trip_id' => 'required',
          'kpi_status' => 'required|string',
        //   'latitude' => 'required',
        //   'longitude' => 'required'

        ];
    
        $validator = Validator::make($data, $rules);
    
        if ($validator-> fails()) {
          return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        
        $delivery_trip_id = $data['delivery_trip_id'];

        if(!isset($data['lat']) || !isset($data['lng']))
        {
            $data['lat']=null;
             $data['lng']=null;

        }
        
    
        $kpi_status = $data['kpi_status'];
        $current_Lat=$data['lat'];
        $current_Lng=$data['lng'];
      
        if($kpi_status == 'pickup_check_in'){
    
          $update_checkin = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->update(['pickup_check_in' => date('Y-m-d H:i:s'),'pickup_latitude' => $current_Lat, 'pickup_longitude' => $current_Lng]);
    
        }elseif($kpi_status == 'dropoff_check_in'){
    
          $update_checkin = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->update(['dropoff_check_in' => date('Y-m-d H:i:s')]);
    
        }else{
          return response()->json([
            "code" => 400,
            "message" => "kpi_status not valid"
          ]);
        }
        
        if($update_checkin){
          return response()->json([
            "code" => 200,
            "message" => "Updated successfully"
          ]);
        }else{
          return response()->json([
            "code" => 400,
            "message" => "Something went wrong"
          ]);
        }
  
      }

    public function loadUnloadStock(Request $request) {
       
        $data = json_decode($request->getContent(),true);
        
        //    Fields Validation      
        $rules = [
          'key' => 'required|string',
          'delivery_trip_id' => 'required',
          'list' => 'array',
          'materials' => 'required'
        ];
    
        $validator = Validator::make($data, $rules);
    
        if ($validator-> fails()) {
          return responseValidationError('Fields Validation Failed.', $validator->errors());
        }


        if(!isset($data['lat']) || !isset($data['lng']))
        {
            $data['lat']=null;
             $data['lng']=null;
        
        }
        $delivery_trip_id = $data['delivery_trip_id'];
        
        
        foreach($data['list'] as $list){
          if($list['name'] == 'Ticket'){
            $ticket = $list['link'];
          }elseif($list['name'] == 'GatePass'){
            $gate_pass = $list['link'];
          }
        }
  
        if(isset($ticket) && $ticket != null){
            $ticket = json_encode($ticket,JSON_UNESCAPED_SLASHES);
          }else{
            return response()->json([
                "code" => 403,
                "success" => false,
                "message" => 'Ticket is missing'
            ]);
          }
   
        
        if(isset($gate_pass) && $gate_pass != null){
          $gate_pass = json_encode($gate_pass,JSON_UNESCAPED_SLASHES);
        }else{
          $gate_pass = "[]";
        }
        $trip_data=DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->get(['vehicle_id','order_id']);
        if(!count($trip_data) > 0 ){
            return response()->json([
                "code" => 400,
                "message" => "Delivery Trip Not Found "
              ]);
        }
       
        $order_id = $trip_data[0]['order_id'];
        $vehicle_id = $trip_data[0]['vehicle_id'];
        $order_info = Order::where('order_id',$order_id)->get(['pickup_address_id','customer_id']);
        $customer_id = $order_info[0]['customer_id']; 
        $address_id = $order_info[0]['pickup_address_id']; 
        $category = checkOrderCatgeory($order_id);
        
   
        $key = $data['key'];
        
        if($key == 'pickup'){   
          foreach($data['materials'] as $material){


            if($category == "CWA"){
                $mat_id = \App\Model\CorporateCustomerMaterial::where('id',$material['material_id'])->value('parent_material_id');
                $material_erp_id = \App\Model\Material::where('material_id',$mat_id)->value('erp_id');
                if($material_erp_id == null){
                    return response()->json([
                        "code" => 400,
                        "message" => "Erp id does not exist against material id ".$material['material_id']
                      ]);
                }
                $driver_erp_id = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->with('vehicle.driver')->get()->pluck('vehicle.driver.erp_id');
                if($driver_erp_id[0] == null){
                     return response()->json([
                        "code" => 400,
                        "message" => "Erp id does not exist against driver of trip id ".$delivery_trip_id
                      ]);
                }
                $unit = Unit::where('id',$material['unit'])->value('key');
                $sap_data['MaterialId'] = $material_erp_id;
                $sap_data["Username"] = (string)$driver_erp_id[0];
                $sap_data["MaterialWeight"] = $material['weight'];
                $sap_data["MaterialUnit"] = isset($unit) ? $unit : null;
        
                $data_sap = json_encode($sap_data);
                $sap_obj = new SapApi();
                $sap_obj->fname = "loadUnloadStock";
                $sap_obj->body = $data_sap;
                $sap_obj->save();
                $response = DeliveryTrip::confirmLoadDriverSAP($sap_data,$sap_obj);
                $response = json_decode((json_decode($response,true)),true);
                
                // $response['d']['StatusCode'] = 201; // to be removed
                // if($response['d']['StatusCode'] != 201){
                //     return response()->json([
                //         "code" => $response['d']['StatusCode'],
                //         "message" => $response['d']['Message'] . " for trip ID ".$delivery_trip_id. " with material ID ".$material['material_id']
                //       ]);
                // }
            }

            $pickup_material =  new PickupMaterial();
            $pickup_material->trip_id = $delivery_trip_id;
            $pickup_material->order_id = $order_id;
            if($category == "CWA"){
                $material['material_id'] = \App\Model\CorporateCustomerMaterial::where('id',$material['material_id'])->value('parent_material_id');
            }
            $pickup_material->material_id = $material['material_id'];
            $pickup_material->weight = $material['weight'];
            $pickup_material->unit = $material['unit'];
            $pickup_material->save();
            if($category == "SKIP_COLLECTION"){

                if(!isset($material['skip_id']) || $material['skip_id'] == null){
                    return response()->json([
                        "code" => 403,
                        "success" => false,
                        "message" => 'Kindly select a skip to proceed'
                    ]);
                }
                $assigned_to = getAssetTransactionSource('VEHICLE');
                $replace = $asset_id = \App\Model\AssetInventory::where('assigned_to',$assigned_to)->where('assignee_id', $vehicle_id)->pluck('asset_id')->first();
                $asset = \App\Model\Skip::where('customer_id',$customer_id)->where('skip_id',$material['skip_id'])->get(['skip_id','asset_id']);
                if(!isset($asset) || !isset($asset[0]) || $asset == null){
                    return response()->json([
                        "code" => 403,
                        "success" => false,
                        "message" => 'Kindly select a skip to proceed'
                    ]);
                }
                
                    

                
                if(isset($replace) && $replace != null){
                    
                    $assigned_to = getAssetTransactionSource('CUSTOMER');
                    \App\Model\AssetTransaction::insert([
                        'asset_id' => $asset_id, 
                        'order_id' => $order_id, 
                        "transaction_date" => date('Y-m-d H:i:s'),
                        "transaction_type" => "transfer_to_client",
                        "transfer_from" => $vehicle_id,
                        "transfer_to" => $customer_id,
                        // "vehicle_id" => $vehicle_id 
                    ]);  
                    
                    \App\Model\Skip::where('asset_id',$asset_id)
                    ->update([
                                        'customer_id' => $customer_id,
                                        'asset_id' => $asset_id,
                                        'material_id' => $material['material_id'],
                                        'status' => 1,
                                        'address_id' => $address_id
                                    ]);
                    \App\Model\AssetInventory::where('asset_id',$asset_id)
                                            ->update(
                                                        [
                                                            'allocated' => 1,
                                                            "assigned_to" => $assigned_to,
                                                            "assignee_id" => $customer_id,
                                                        ]
                                                    );

                                                    
                    
                   
                }

                
                ##Receival for skip from customer
                $assigned_to = getAssetTransactionSource('VEHICLE');
                \App\Model\AssetTransaction::insert([
                    'asset_id' => $asset[0]['asset_id'], 
                    'order_id' => $order_id, 
                    "transaction_date" => date('Y-m-d H:i:s'),
                    "transaction_type" => "transfer_from_client",
                    "transfer_from" => $customer_id,
                    "transfer_to" => $vehicle_id,
                ]);  
                // \App\Model\Vehicle::where('vehicle_id',$vehicle_id)->orderBy('vehicle_id', 'desc')->where('deleted_at',null)->whereStatus(1)->first()->update(['asset_id' => $asset[0]['asset_id']]);
                $skip_id = \App\Model\Skip::where('skip_id',$asset[0]['skip_id']);
                $skip_id->update(['customer_id' => null , 'address_id' => null]);
                \App\Model\AssetInventory::where('asset_id',$asset[0]['asset_id'])
                                            ->update(
                                                [
                                                    'allocated' => 0,
                                                    "assigned_to" => $assigned_to,
                                                    "assignee_id" => $vehicle_id,
                                                ]
                                            );
                $skip_id = $skip_id->value('skip_id');
                $pickup_material->skip_id = $skip_id;
                $pickup_material->save();
            }
            elseif($category == "ASSET"){
                if(!isset($material['skip_id']) || $material['skip_id'] == null ){
                    return response()->json([
                        "code" => 400,
                        "message" => "Missing skip id "
                      ]);
                }
                $assigned_to = getAssetTransactionSource('VEHICLE');
                
                \App\Model\AssetInventory::where('asset_id',$material['skip_id'])
                                            ->update(
                                                [
                                                    "assigned_to" => $assigned_to,
                                                    "assignee_id" => $vehicle_id,
                                                ]
                                            );
            $pickup_material->asset_id = $material['skip_id'];

            }
           
   
          }
          $pickup_material->e_ticket = $ticket;
          $pickup_material->gate_pass = $gate_pass;
          $pickup_material->save();
  
          $update_delivery_trip_load = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->update(['load' => date('Y-m-d H:i:s')]);
          $delivery_trip =  DB::select('select vehicles.driver_id,delivery_trips.order_id,trip_startime,start_latitude,start_longitude,delivery_trips.vehicle_id,pickup_check_in,dropoff_check_in,unload,delivery_trips.load from delivery_trips
          inner join vehicles on vehicles.vehicle_id = delivery_trips.vehicle_id
          where delivery_trips.delivery_trip_id = ' . $delivery_trip_id);
          
$pickup_check_in= $delivery_trip[0]->pickup_check_in;
$load= $delivery_trip[0]->load;

$pickup_check_in = Carbon::createFromFormat('Y-m-d H:i:s', '' . $pickup_check_in);

$load = Carbon::createFromFormat('Y-m-d H:i:s', '' . $load);

$diff_in_pst = $pickup_check_in->diffInMinutes($load);

$totalpstime = gmdate("H:i", ($diff_in_pst * 60));
$update_delivery_trip = DeliveryTrip::where('delivery_trip_id', $delivery_trip_id)
->update(['actual_pstime'=>$totalpstime]);


        
  
        }elseif($key == 'dropoff'){

           
            if($category == "CWA"){
                
                foreach($data['materials'] as $material){

                    $sap_material = [];
                    $material_data = \App\Model\CorporateCustomerMaterial::where('id',$material['material_id'])->first(['parent_material_id','child_material_desc as name','child_material_code as material_code']);
                    $material_parent_data = Material::where('material_id',$material_data['parent_material_id'])->first(['name','material_code']);
                   
                    if(!isset($material_data) || $material_data == null){
                        return response()->json([
                            "code" => 404,
                            "message" => "Material not found for material id ".$material['material_id']
                        ]);
                    }
                    
                    $material_parent_data['name'] = json_decode($material_parent_data['name']);
                    $material_parent_data['name'] = $material_parent_data['name']->en;
        
                    $material_data['name'] = json_decode($material_data['name']);
                    $material_data['name'] = $material_data['name']->en;
                    
                    $unit = Unit::where('id',$material['unit'])->value('key');
                    $sap_material_data = [  
                                            "MaterialidParent" => $material_parent_data['material_code'],
                                            "MaterialParentCodeName" => $material_parent_data['name'],
                                            "Materialweight" => $material['weight'],
                                            "MaterialidCustomer" => $material_data['material_code'],
                                            "MaterialCustomerCodeName" => $material_data['name'],
                                            "Materialunitid" => $unit

                                        ];
                    array_push($sap_material,$sap_material_data);

                }
                $driver_erp_id = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->with('vehicle.driver')->get()->pluck('vehicle.driver.erp_id');
                if($driver_erp_id[0] == null){
                    return response()->json([
                        "code" => 400,
                        "message" => "Erp id does not exist against driver of trip id ".$delivery_trip_id
                    ]);
                }
              
                $corporate_cust_erp_id = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->with('customer_dropoff')->get()->pluck('customer_dropoff.erp_id');
                $corporate_cust_erp_id = $corporate_cust_erp_id[0];
                if($corporate_cust_erp_id[0] == null){
                    return response()->json([
                        "code" => 400,
                        "message" => "Erp id does not exist against corporate customer of trip id ".$delivery_trip_id
                    ]);
                }

                if(isset($data['corporate_customer_id']) && $data['corporate_customer_id'] != "" && $data['corporate_customer_id'] != null){

                    $corporate_cust_erp_id = \App\Model\Address::where('address_id',$data['corporate_customer_id'])->value('erp_id');
                    if($corporate_cust_erp_id == null){
                        return response()->json([
                            "code" => 400,
                            "message" => "Erp id does not exist against this address of trip id ".$delivery_trip_id
                        ]);
                    }
                    DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->update(['customer_dropoff_loc_id' => $data['corporate_customer_id']]);
                   
                }
                $part_trip_inf["Customerid"] = (string)$corporate_cust_erp_id;
                $part_trip_inf["Tripno"] = (string)$delivery_trip_id;
                $part_trip_inf["MATERIALS"] = $sap_material;
                $part_trip_info = [$part_trip_inf];

                if(isset($data['reason_id']) && $data['reason_id'] != "" && $data['reason_id'] != null && $data['reason_id'] != 0)
                {   
                    
                    $cancel_reasons = \App\Model\CancelReason::where('status',1)
                                        ->where('erp_id','!=',null)
                                        ->where('cancel_reason_id',$data['reason_id'])
                                        ->get(['reason','erp_id'])->first();
                    $cancel_reasons->reason = json_decode($cancel_reasons->reason);
                    $cancel_reasons->reason = $cancel_reasons->reason->en;
                    

                    $change_cust["ReasonId"] = isset($cancel_reasons->erp_id) ? (string)$cancel_reasons->erp_id : null;
                    $change_cust["ReasonDesc"] = isset($cancel_reasons->reason) && $cancel_reasons->reason != null ? $cancel_reasons->reason : null;
                    $change_customer = [$change_cust];
                    $sap_data["CHANGECUST"] = $change_customer;

                }
               

                

                $sap_data['CwaImage'] = "";
                $sap_data["StatusCode"] = "";
                $sap_data["DriverId"] = $driver_erp_id[0];
                $sap_data["WasteImage"] = "";
                $sap_data["Message"] = "";
                $sap_data["DriverName"] = "";
                $sap_data["DriverContact"] = "";
                $sap_data["TripNo"] = "";
                $sap_data["Branch"] = "";
                $sap_data["Vehino"] = "";
                $sap_data["DateTime"] = "";
                $sap_data["CustomerId"] = (string)$corporate_cust_erp_id;
                $sap_data["CustomerName"] = "";
                $sap_data["DeliveredQty"] = "0.000";
                $sap_data["ReturnedQty"] = "0.000";
                $sap_data["Unit"] = "";
                $sap_data["PART_TRIP_INFO"] = $part_trip_info;


                // return $sap_data;
            
                $data_sap = json_encode($sap_data);
                $sap_obj = new SapApi();
                $sap_obj->body = $data_sap;
                $sap_obj->fname = "loadUnloadStock";
                $sap_obj->save();
                $response = DeliveryTrip::confirmUnLoadDriverSAP($sap_data,$sap_obj);
                // return $response;
                // $response = json_decode($response,true);
                $response = json_decode($response,true);
                $response['d']['StatusCode'];
                // $response['d']['StatusCode'] = 201; // to be removed

                // if($response['d']['StatusCode'] != 201){
                //     return response()->json([
                //         "code" => $response['d']['StatusCode'],
                //         "message" => $response['d']['Message'] . " for trip ID ".$delivery_trip_id. " with material ID ".$material['material_id']
                //       ]);
                // }
                
            }

        foreach($data['materials'] as $material){

            $dropoff_material =  new DropoffMaterial();
            $dropoff_material->trip_id = $delivery_trip_id;
            $dropoff_material->order_id = $order_id;
            if($category == "CWA"){
                $material_id = \App\Model\CorporateCustomerMaterial::where('id',$material['material_id'])->value('parent_material_id');
            }else{
                $material_id = $material['material_id'];
            }
            $dropoff_material->material_id = $material_id;
            $dropoff_material->weight = $material['weight'];
            $dropoff_material->unit = $material['unit'];
            $dropoff_material->save();

            // if($category == "CWA"){
            //     \App\Model\PickupMaterial::firstOrCreate(
            //         [   'trip_id' => $delivery_trip_id , 'material_id' => $material_id],[ 'weight' =>  0]);
            // }
            if($category == "ASSET"){
                if(!isset($material['skip_id']) || $material['skip_id'] == null ){
                    
                    return response()->json([
                        "code" => 400,
                        "message" => "Missing skip id "
                      ]);
                }
                $assigned_to = getAssetTransactionSource('CUSTOMER');
                
                \App\Model\AssetInventory::where('asset_id',$material['skip_id'])
                                            ->update(
                                                [
                                                    "assigned_to" => $assigned_to,
                                                    "assignee_id" => $customer_id,
                                                ]
                                            );
                $customer_address_id = \App\Model\DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->value('customer_dropoff_loc_id');
                \App\Model\Skip::updateOrCreate(['asset_id' => $material['skip_id']],
                                                ['material_id' => $material['material_id'],
                                                 'address_id' => $customer_address_id,
                                                 'customer_id' => $customer_id,
                                                 'status' => 1]);

            }
          }
          $dropoff_material->e_ticket = $ticket;
          $dropoff_material->gate_pass = $gate_pass;
          $dropoff_material->save();

          $update_delivery_trip_unload = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->update(['unload' => date('Y-m-d H:i:s')]);
          $delivery_trip =  DB::select('select vehicles.driver_id,delivery_trips.order_id,trip_startime,start_latitude,start_longitude,delivery_trips.vehicle_id,pickup_check_in,dropoff_check_in,unload,delivery_trips.load from delivery_trips
          inner join vehicles on vehicles.vehicle_id = delivery_trips.vehicle_id
          where delivery_trips.delivery_trip_id = ' . $delivery_trip_id);
          $dropoff_check_in= $delivery_trip[0]->dropoff_check_in;
$unload= $delivery_trip[0]->unload;


$dropoff_check_in = Carbon::createFromFormat('Y-m-d H:i:s', '' . $dropoff_check_in);
$unload = Carbon::createFromFormat('Y-m-d H:i:s', '' . $unload);

$diff_in_dst = $dropoff_check_in->diffInMinutes($unload);
$totaldstime = gmdate("H:i", ($diff_in_dst * 60));
$update_delivery_trip = DeliveryTrip::where('delivery_trip_id', $delivery_trip_id)
->update(['actual_dstime'=>$totaldstime]);


$delivery_trip =  DB::select('select vehicles.driver_id,delivery_trips.order_id,trip_startime,start_latitude,start_longitude,pickup_latitude,pickup_longitude,delivery_trips.vehicle_id,pickup_check_in,dropoff_check_in,unload,delivery_trips.load from delivery_trips
inner join vehicles on vehicles.vehicle_id = delivery_trips.vehicle_id
where delivery_trips.delivery_trip_id = ' . $delivery_trip_id);

//Calculate Total Distance
$lat1 = $delivery_trip[0]->start_latitude;
$long1 = $delivery_trip[0]->start_longitude;

$lat2 = $delivery_trip[0]->pickup_latitude;
$long2 = $delivery_trip[0]->pickup_longitude;

if($lat1 == NULL || $long1 == NULL || $lat2 == NULL || $long2 == NULL)
{
    $pickup_distance=0;
}
else
{
    
    $pickup_distance = DeliveryTrip::distance($lat1, $long1, $lat2, $long2);
}

$latFrom = $delivery_trip[0]->pickup_latitude;
$longFrom = $delivery_trip[0]->pickup_longitude;

$latTo = $data['lat'];
$longTo = $data['lng'];

if($latFrom==NULL || $longFrom==NULL || $latTo==NULL || $longTo==NULL)
{
    $dropoff_distance=0;
}
else
{
    $dropoff_distance = DeliveryTrip::distance($latFrom, $longFrom, $latTo, $longTo);
}

$total_distance=$pickup_distance + $dropoff_distance;


//Calculate Total Time

$endtime = date('Y-m-d H:i:s');

$starttime = $delivery_trip[0]->trip_startime;

$to = Carbon::createFromFormat('Y-m-d H:i:s', '' . $starttime);
$from = Carbon::createFromFormat('Y-m-d H:i:s', '' . $endtime);

$diff_in_minutes = $to->diffInMinutes($from);
$hours = floor($diff_in_minutes / 60);
$min = $diff_in_minutes - ($hours * 60);
$totaltime = $hours.":".$min;
if (count($delivery_trip) > 0) {

    if (isset($delivery_trip[0]->order_id) && $delivery_trip[0]->order_id != '') {
        $order_id = $delivery_trip[0]->order_id;
    } else {
        return response()->json([
            "code" => 403,
            "success" => false,
            "resp" => 'Order ID is missing',
        ]);
    }

    $update_delivery_trip = DeliveryTrip::where('delivery_trip_id', $data['delivery_trip_id'])
    ->update(['trip_status_id' => 4, 'trip_endtime' => date('Y-m-d H:i:s'),'actual_time'=>$totaltime,
    'actual_distance'=>$total_distance,'dropoff_latitude' => $data['lat'],'dropoff_longitude' => $data['lng']]);
    Vehicle::where('vehicle_id',$delivery_trip[0]->vehicle_id)->update(['job_order' => null]);

    if ($update_delivery_trip) {



        // Log Trip
        $logdata[] = [
            'trip_id' => $data['delivery_trip_id'],
            'order_id' => $order_id,
            'trip_status_id' =>  4,
            'created_at' =>  date('Y-m-d H:i:s')
        ];
        TripLogs::insert($logdata);

        return response()->json([
            "code" => 200,
            "success" => true,
            "message" => 'Delivery trip ended succesfully'
        ]);
    } else {
        return response()->json([
            "code" => 403,
            "success" => false,
            "message" => 'Something went wrong'
        ]);
    }
  
        }
        else{
          return response()->json([
            "code" => 400,
            "message" => "Delivery Trip Not Found "
          ]);
        }
        
     
      }
      return response()->json([
        "code" => 200,
        "message" => "Material added successfully"
      ]);
}


public function cancelDeliveryTripAction(Request $request)
{

    $data =  json_decode($request->getContent(), true);

    $rules = [
        'trip_id' => 'required|integer|min:1|exists:delivery_trips,delivery_trip_id',
    ];

    $validator = Validator::make($data, $rules);
    if ($validator->fails()) {

        return responseValidationError('Fields Validation Failed.', $validator->errors());
    }

    $trip_code = "";
    try {

        $trip_status_id = \App\Model\TripStatus::where('trip_status_title', 'like', '%' . "Cancel" . '%')->value('trip_status_id');
        $trip_id = $data['trip_id'];
        $trip_status = getTripStatus($data['trip_id']);
        
        if($trip_status == "CANCEL"){
            return response()->json([
                "code" => 400, // Bad Request
                "message" => "Trip has already been canceled"
            ]);
        }elseif($trip_status == "ASSIGNED"){
            $cancelDeliveryTrip = DeliveryTrip::where('delivery_trip_id', $trip_id)->update(['trip_status_id' => $trip_status_id]); // Cancel Trip
            $trip_data = DeliveryTrip::where('delivery_trip_id', $trip_id)->get(['vehicle_id','order_id',"trip_code"])->first(); // Cancel Trip
            $vehicle_id = $trip_data['vehicle_id'];
            $order_id = $trip_data['order_id'];
            $trip_code = $trip_data['trip_code'];
            if ($trip_id) {
                // Log Trip
                $logdata[] = [
                    'trip_id' => $trip_id,
                    'order_id' => $order_id,
                    'trip_status_id' =>  $trip_status_id,
                    'created_at' =>  date('Y-m-d H:i:s')
                ];
                TripLogs::insert($logdata);
            }
            $this->tripnotification($vehicle_id, $trip_id,'Trip Canceled','Trip Has Been Canceled Against Trip Code # ' . $trip_code);
          

            return response()->json([
                "code" => 200,
                "message" => "Trip canceled SuccessFully"
            ]);
        
        }elseif($trip_status == "STARTED"){
            
            $cancelDeliveryTrip = DeliveryTrip::where('delivery_trip_id', $trip_id);
            $dropoff_check_in = $cancelDeliveryTrip->value('dropoff_check_in');

            if($dropoff_check_in == null){
                $cancelDeliveryTrip->update(['trip_status_id' => $trip_status_id]); // Cancel Trip
                $trip_data = DeliveryTrip::where('delivery_trip_id', $trip_id)->get(['vehicle_id','order_id',"trip_code"])->first(); // Cancel Trip
                $vehicle_id = $trip_data['vehicle_id'];
                $order_id = $trip_data['order_id'];
                $trip_code = $trip_data['trip_code'];
                if ($trip_id) {
                    // Log Trip
                    $logdata[] = [
                        'trip_id' => $trip_id,
                        'order_id' => $order_id,
                        'trip_status_id' =>  $trip_status_id,
                        'created_at' =>  date('Y-m-d H:i:s')
                    ];
                    TripLogs::insert($logdata);
                }
                $this->tripnotification($vehicle_id, $trip_id,'Trip Canceled','Trip Has Been Canceled Against Trip Code # ' . $trip_code);
            }else{
                return response()->json([
                    "code" => 412, //PreCondition Failed
                    "message" => "This trip cannot be canceled"
                ]);
            }
            
          

            return response()->json([
                "code" => 200,
                "message" => "Trip canceled SuccessFully"
            ]);
        
        }
        
        else{
            return response()->json([
                "code" => 412, //PreCondition Failed
                "message" => "This trip cannot be canceled"
            ]);
        }
       
    

        }catch (\Exception $ex) {
            $response = [
                "code" => 500,
                "data" => [
                    "trip_id" => $trip_id,
                    "processing_results" => ["Error in processing!"],
                    "error" => $ex->getMessage()
                ],
                'message' => 'Error in canceling trip.'
            ];
            return response()->json($response);
        }
       

    
}

public function assignedMaterialChangeSAP(Request $request) {
    $errors = [];
    $data = json_decode($request->getContent(),true);
    $code = 204; $message = "Materials Updated successfully";
    //    Fields Validation      
    $rules = [
        'delivery_trip_id' => 'required|string',
        'materials' => 'required'
        // 'key' => 'required|string',
        // 'list' => 'required|array',
    ];

    $validator = Validator::make($data, $rules);
    if ($validator-> fails()) { return responseValidationError('Fields Validation Failed.', $validator->errors()); }

    $request_log_id = "";

    foreach($data['materials'] as $mat_ky => $material){
        $mat_det = Material::where("erp_id",$material['material_id'])->get("material_id")->toArray();
        if(count($mat_det)==0){
            Error::trigger("trip.materialerpid", ["Material Id ".$material['material_id']." is not present in AQG."]);
            array_push($errors, \App\Message\Error::get('trip.materialerpid'));
            return respondWithError($errors,$request_log_id,404);
        }
        $data['materials'][$mat_ky]['aqg_id'] = $mat_det[0]['material_id'];
    }

    $delivery_trip_id = $data['delivery_trip_id'];
    $trip_det = DeliveryTrip::find($delivery_trip_id);
    if(!is_object($trip_det)){
        Error::trigger("trip.exists", ["Trip Id ".$material['material_id']." does not exists in AQG."]);
        array_push($errors, \App\Message\Error::get('trip.exists'));
        return respondWithError($errors,$request_log_id,404);
    }
    $order_id = $trip_det->order_id;

    $created_by = "";
    $user_details = auth()->user();
    if(isset($user_details->user_id)){ $created_by = $user_details->user_id; }
    else if(isset($user_details->customer_id)){ $created_by = $user_details->customer_id; }

    // $key = $data['key'];
    $mat_ids = array();
    if(count($data['materials'])>0)
    {
        foreach($data['materials'] as $material){
         $trip_Materials = TripAssignedMaterial::updateOrCreate(
            ['delivery_trip_id'=> $delivery_trip_id, 'material_id' => $material['aqg_id'] ],
            [
                'weight' => isset($material['weight'])? $material['weight']:NULL,
                'unit' => isset($material['unit'])? $material['unit']:NULL,
                'deleted_at' => NULL,
                'updated_by' => $created_by,
                'updated_at' =>  date('Y-m-d H:i:s'),
            ]);
            array_push($mat_ids,$trip_Materials->id);
            OrderMaterial::firstOrCreate(
                ['order_id' => $order_id, 'material_id' => $material['aqg_id'] ],
                [
                    'weight' => isset($material['weight'])? $material['weight']:NULL,
                    'unit' => isset($material['unit'])? $material['unit']:NULL,
                    'remarks' => "Added By SAP User",
                    'status' => 1,
                    'updated_at' =>  date('Y-m-d H:i:s')
                ]
            );
        }
    }
    $del_qry = TripAssignedMaterial::where('delivery_trip_id',$delivery_trip_id);
    if(count($mat_ids)>0){ $del_qry = $del_qry->whereNotIn("id",$mat_ids); }
    $del_qry->update( ['deleted_at' => date('Y-m-d H:i:s'),'updated_by' => $created_by] );

    return respondWithSuccess(null, "TRIP", $request_log_id, $message, $code);
}

    function changeTripAddress(Request $request){
        
        $data = json_encode(request()->post());
        $data =  json_decode($data,true);

        $validator = Validator::make($request->all(), [
        'delivery_trip_id' => 'required|integer|exists:delivery_trips,delivery_trip_id',
        'pickup_address_id' => 'nullable|integer',
        'dropoff_address_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $delivery_trip_id = $data['delivery_trip_id'];

        if(isset($data['pickup_address_id']) && $data['pickup_address_id'] != null || isset($data['dropoff_address_id']) && $data['dropoff_address_id'] != null){
            $trip_data = DeliveryTrip::where('delivery_trip_id',$data['delivery_trip_id'])->get(['order_id','vehicle_id','trip_code','dropoff_check_in']);
            
            $order_id = $trip_data[0]->order_id;
            $vehicle_id = $trip_data[0]->vehicle_id;
            $trip_code = $trip_data[0]->trip_code;
            $dropoff_check_in = $trip_data[0]->dropoff_check_in;

            if($dropoff_check_in != null){
                return response()->json([
                    "code" => 412, //PreCondition Failed
                    "message" => "This trip cannot be modified, the driver has checked in to the drop-off location already."
                ]);
            }
            
            $category = checkOrderCatgeory($order_id);

            //To prepare data to be stored based on order categories
            if($category == "ASSET"){ 
                // $data['aqg_pickup_loc_id'] = $data['pickup_address_id'];
                $data['customer_dropoff_loc_id'] = $data['dropoff_address_id'];

            }elseif($category == "PICKUP"){

                // $data['customer_pickup_loc_id'] = $data['pickup_address_id'];
                $data['aqg_dropoff_loc_id'] = $data['dropoff_address_id'];
                
            }elseif($category == "TRANSFER"){

                // $data['customer_pickup_loc_id'] = $data['pickup_address_id'];
                $data['customer_dropoff_loc_id'] = $data['dropoff_address_id'];

            }elseif($category == "CWA"){

                // $data['customer_pickup_loc_id'] = $data['pickup_address_id'];
                $data['customer_dropoff_loc_id'] = $data['dropoff_address_id'];

            }
            elseif($category == "SKIP_COLLECTION"){

                // $data['customer_pickup_loc_id'] = $data['pickup_address_id'];
                $data['aqg_dropoff_loc_id'] = $data['dropoff_address_id'];

            }
        }
        unset($data['delivery_trip_id']);
        unset($data['pickup_address_id']);
        unset($data['dropoff_address_id']);
        // $data = array_filter($data);
        // if($data == null){
        //     return $response = [
        //         "code" => 500,
        //         "data" => "",
        //         'message' => 'No data found.'
        //     ];
        // }
        DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->update($data);
        $result = $this->tripnotification($vehicle_id, $delivery_trip_id,"Trip Modified","Control Tower has modified the pickup/drop-off location of this trip. Trip Code is " . $trip_code);
        if($result != null){
            return $result;
        }
        return response()->json([
            "code" => 200,
            "data" => "",
            "message" => "Trip updated SuccessFully"
        ]);

    }
    function tripPickupLocation($order_number){
        
        $validator = Validator::make([    
            'order_number' => $order_number
        ],[
            'order_number' => 'required|integer|exists:orders,order_number'
        ]);
        if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $order_details = Order::where('order_number',$order_number)->get(['order_id','customer_id']);
        $order_id = $order_details[0]->order_id;
        $customer_id = $order_details[0]->customer_id;
        if(checkOrderCatgeory($order_id) == "PICKUP"){
            $pickup_locations = Address::where('customer_id',$customer_id)->get(['address_id','address_title']);
            $dropoff_locations = Store::get(['store_id as address_id','store_name as address_title']);

        }
        elseif(checkOrderCatgeory($order_id) == "TRANSFER"){
            $pickup_locations = Address::where('customer_id',$customer_id)->get(['address_id','address_title']);
            $dropoff_locations = Address::where('customer_id',$customer_id)->get(['address_id','address_title']);


        }
        elseif(checkOrderCatgeory($order_id) == "CWA"){
            $pickup_locations = Address::where('customer_id',$customer_id)->get(['address_id','address_title']);
            $dropoff_locations = Address::where('customer_id',$customer_id)->get(['address_id','address_title']);


        }
        elseif(checkOrderCatgeory($order_id) == "SKIP_COLLECTION"){
            $pickup_locations = Address::where('customer_id',$customer_id)->get(['address_id','address_title']);
            $dropoff_locations = Store::get(['store_id as address_id','store_name as address_title']);


        }elseif(checkOrderCatgeory($order_id) == "ASSET"){
            $pickup_locations = Store::get(['store_id','store_name']);
            $dropoff_locations = Address::where('customer_id',$customer_id)->get(['address_id','address_title']);

        }
        
        return response()->json([
            "code" => 200,
            "data" => [
                "pickup_addresses" => $pickup_locations,
                "dropoff_addresses" => $dropoff_locations
            ],
            "message" => "Trip data fetched SuccessFully"
        ]);


    }

    function loadMaterialFromSAP($delivery_trip_id,$status_code){
            
        
        $validator = Validator::make([    
            'delivery_trip_id' => $delivery_trip_id
        ],[
            'delivery_trip_id' => 'required|integer|exists:delivery_trips,delivery_trip_id'
        ]);

        if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $order_id = DeliveryTrip::where('delivery_trip_id',$delivery_trip_id)->value('order_id');
        $sap_obj = new SapApi();
        $sap_obj->fname = "loadMaterialFromSAP";
        $sap_obj->save();
        $response = DeliveryTrip::loadMaterialSAP($delivery_trip_id,$status_code,$sap_obj);
        // $delivery_trip_str = (string)$delivery_trip_id;  // to be removed

        // $response = [  // to be removed
        //     "d"=> [
        //         "__metadata"=> [
        //             "id"=> "http://88.85.251.150:8001/sap/opu/odata/sap/ZAQI_CWA_SALES_SRV/AQGNOW_CONFIRMLOADSet(AqgnowRef='23006218',StatusCode='dropoff')",
        //             "uri"=> "http://88.85.251.150:8001/sap/opu/odata/sap/ZAQI_CWA_SALES_SRV/AQGNOW_CONFIRMLOADSet(AqgnowRef='23006218',StatusCode='dropoff')",
        //             "type"=> "ZAQI_CWA_SALES_SRV.AQGNOW_CONFIRMLOAD"
        //         ],
        //         "AqgnowRef"=> "23006218",
        //         "StatusCode"=> $delivery_trip_str,
        //         "AQGNOW_MATERIALSSet"=> [
        //             "results"=> [
        //                 [
        //                     "__metadata"=> [
        //                         "id"=> "http://88.85.251.150:8001/sap/opu/odata/sap/ZAQI_CWA_SALES_SRV/AQGNOW_MATERIALSSet('000000000100200101')",
        //                         "uri"=> "http://88.85.251.150:8001/sap/opu/odata/sap/ZAQI_CWA_SALES_SRV/AQGNOW_MATERIALSSet('000000000100200101')",
        //                         "type"=> "ZAQI_CWA_SALES_SRV.AQGNOW_MATERIALS"
        //                     ],
        //                     "MaterialCode"=> "000000000100200101",
        //                     "MaterialName"=> "Cu Wire Cable",
        //                     "Weight"=> "24.470",
        //                     "Unit"=> "TONS"
        //                 ]

        //             ]
        //         ]
        //     ]
        // ];

        // TripAssignedMaterial::where('trip_id',$delivery_trip_id)->update(['weight' => 5, 'unit' => 1, 'material_id' => 402])
        $response = json_encode($response) ;
        $response = json_encode($response) ;
        $response = json_encode($response) ;
        
        $response = json_decode(json_decode(json_decode($response, true), true), true);
        
        if(empty($response['d']['AQGNOW_MATERIALSSet']['results'])){

            
            ##To be removed
            TripAssignedMaterial::where('delivery_trip_id',$delivery_trip_id)
            ->update( ['deleted_at' => date('Y-m-d H:i:s')] );
            TripAssignedMaterial::updateOrCreate(
                ['delivery_trip_id'=> $delivery_trip_id ,  'material_id' => 402],
 
                [
                    'weight' => 10,
                    'unit' => 1,
                    'deleted_at' => NULL,
                    'updated_at' =>  date('Y-m-d H:i:s'),
                ]);
             
            
 
           
 

            return response()->json([
                "code" => 200,
                "data" => "",
                "message" => "Loaded SAP Material successfully"
            ]);
            ###

            // return response()->json([
            //     "code" => 412, //PreCondition Failed
            //     "message" => "Material hasn't been loaded from SAP yet; against trip id ".$delivery_trip_id
            // ]);

        }
        $sap_material = $response['d']['AQGNOW_MATERIALSSet']['results'];
        $mat_codes = [];
        foreach($sap_material as $material){
            array_push($mat_codes,$material['MaterialCode']);
        }
        $mat_data = Material::whereIn("material_code",$mat_codes)->pluck("material_id","material_code")->toArray();
        // array_difference between material in aqg table and material against this trip in SAP
        $array_diff = array_diff($mat_codes,array_keys($mat_data));
       
        if(count($array_diff) > 0){
            $message = 'Material ids do not exist in aqg for material code:' . " ";

            foreach (array_keys($mat_data) as $item) {
                $message .= $item . " ";
            }
            
            return response()->json([
                "code" => 412, //PreCondition Failed
                "message" => $message
            ]);
        }
        $unit = \App\Model\Unit::pluck('id','key');
        foreach($sap_material as $material){
            $trip_Materials = TripAssignedMaterial::updateOrCreate(
               ['delivery_trip_id'=> $delivery_trip_id ,  'material_id' => $mat_data[$material['MaterialCode']]],

               [
                   'weight' => isset($material['Weight'])? $material['Weight']:NULL,
                   'unit' => isset($unit[$material['Unit']])? $unit[$material['Unit']]:NULL,
                   'deleted_at' => NULL,
                   'updated_at' =>  date('Y-m-d H:i:s'),
               ]);
            
           }

           if(count($mat_data)>0){
            $del_qry = TripAssignedMaterial::where('delivery_trip_id',$delivery_trip_id)->whereNotIn("material_id",array_values($mat_data))
            ->update( ['deleted_at' => date('Y-m-d H:i:s')] );

           } 
           
           return response()->json([
            "code" => 200,
            "data" => "",
            "message" => "Loaded SAP Material successfully"
        ]);
           
          
    }

    public function showUnpostedPostedTrips(Request $request){
        
        $data =  json_decode($request->get("data"), true);
        // $data = $request->all();
       
            $trips = DeliveryTrip::select('delivery_trip_id','trip_date','actual_distance','total_distance as planned_distance',
                     'total_time as planned_distance','actual_time','trip_code','vehicle_id','order_id','trip_status_id','customer_dropoff_loc_id','aqg_dropoff_loc_id',
                     'customer_pickup_loc_id','aqg_pickup_loc_id','created_at','posted_date',
                      DB::raw('actual_pstime + actual_dstime as actual_service_time,pickup_service_time + dropoff_service_time as planned_service_time'))
                     ->with('order:order_id,order_number,customer_id,pickup_address_id','order.customer:customer_id,name','order.address:address_id,address_title,address',
                     'trip_status:trip_status_id,trip_status_title','vehicle:vehicle_id,vehicle_plate_number,driver_id','aqg_pickup:store_id,store_name,address',
                     'vehicle.driver:user_id,first_name,last_name','customer_dropoff:address_id,address_title,address,erp_id','aqg:store_id,store_name,address',
                     'order.customer.corporate_customer_addresses:customer_id,address_id,address,latitude,longitude,erp_id,address_title',
                     'order.customer.material:material_id,customer_id,erp_id,material_code,name','pickup_material:trip_id,e_ticket','dropoff_material:trip_id,material_id,weight,unit,e_ticket',
                     'dropoff_material.material.corporate_customer_material:id,parent_material_id,child_material_desc,corporate_cust_address_id')
                     ->orderBy('created_at','DESC')
                     ->whereHas('order.category', function($q){
                        $q->where('key', '=', 'CWA');
                    })
                     ->whereHas('trip_status', function($q){
                        $q->where('key', '=', 'CLOSED');
                    });

            if(isset($data['posted']) && $data['posted'] != null && $data['posted'] == true){ $trips = $trips->where('posted_date','!=',null);}
            else{ $trips = $trips->where('posted_date',null);}

            if(isset($data['startDate']) && !empty($data['startDate']) && isset($data['endDate']) && !empty($data['endDate'])){
                $trips = $trips->whereDate('posted_date','>=',$data['startDate']);
                $trips = $trips->whereDate('posted_date','<=',$data['endDate']);
                
              }

            $corporate_customer_material = \App\Model\CorporateCustomerMaterial::where('status',1)->get()->toArray();
             
      
            $trips = $trips->take(100)->orderBy('delivery_trips.delivery_trip_id','desc')->get()->toArray();
            $units = Unit::where('status',1)->get()->toArray();
            $cancel_reasons = \App\Model\CancelReason::where('status',1)
            ->where('erp_id','!=',null)
            ->get(['reason','erp_id']);

            //Set addresses for pickup and dropoff
            foreach($trips as &$trip){
                
                if($trip['customer_dropoff'] != null){
                    $trip['dropoff_location']['address_id'] = $trip['customer_dropoff']['address_id'];
                    $trip['dropoff_location']['address_title'] = $trip['customer_dropoff']['address_title'];
                    $trip['dropoff_location']['address'] = $trip['customer_dropoff']['address'];
                    $trip['dropoff_location']['erp_id'] = $trip['customer_dropoff']['erp_id'];
                }
                elseif($trip['aqg'] != null){
                    $trip['dropoff_location']['address_id'] = $trip['aqg']['store_id'];
                    $trip['dropoff_location']['address_title'] = $trip['aqg']['store_name'];
                    $trip['dropoff_location']['address'] = $trip['aqg']['address'];
                }
                if($trip['order']['address'] != null){
                    $trip['pickup_location']['address_id'] = $trip['order']['address']['address_id'];
                    $trip['pickup_location']['address_title'] = $trip['order']['address']['address_title'];
                    $trip['pickup_location']['address'] = $trip['order']['address']['address'];
                }
                elseif($trip['aqg_pickup'] != null){
                    $trip['pickup_location']['address_id'] = $trip['aqg_pickup']['store_id'];
                    $trip['pickup_location']['address_title'] = $trip['aqg_pickup']['store_name'];
                    $trip['pickup_location']['address'] = $trip['aqg_pickup']['address'];
                }
                
                unset($trip['customer_dropoff']);
                unset($trip['aqg']);
                unset($trip['order']['address']);
                unset($trip['aqg_pickup']);


                foreach($trip['dropoff_material'] as &$dropoff_mat){ //foreach loop for multiple dropoff material of a trip
                    $dropoff_mat['e_ticket'] = json_decode($dropoff_mat['e_ticket']);
                    // return $dropoff_mat['material']['corporate_customer_material'];
                    if(isset($dropoff_mat['material']['corporate_customer_material']) && $dropoff_mat['material']['corporate_customer_material'] != null){
                        foreach($dropoff_mat['material']['corporate_customer_material'] as $key => &$material){
                            if($material['corporate_cust_address_id'] == $trip['customer_dropoff_loc_id']){
                                // return 
                                $dropoff_mat['material'] = $dropoff_mat['material']['corporate_customer_material'][$key];
                                $dropoff_mat['material']['material_id'] = $dropoff_mat['material']['id'];
                                $dropoff_mat['material']['name'] = $dropoff_mat['material']['child_material_desc'];
                                break;
                            }
                        }
                    }
                    
                }

                if($trip['posted_date'] == null){
                    $trip['posted'] = false;
                }else{
                    $trip['posted'] = true;
                }

              
            }




        return response()->json([
            "code" => 200,
            "data" => [
                'trips' => $trips,
                'units' => $units,
                'corporate_customer_material' => $corporate_customer_material,
                'cancel_reasons' => $cancel_reasons
            ],
            "message" => "trips loaded successfully"
        ]);
    }

    public function modifyTripData(Request $request){
        
        $data =  json_decode($request->getContent(),true);

        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:delivery_trips,delivery_trip_id',
            'corporate_customer_id' => 'exists:addresses,erp_id'
          ]);
          if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
          }

        $material = $data['material'];
        $customer_dropoff_id = isset($data['corporate_customer_id']) && $data['corporate_customer_id'] != null && $data['corporate_customer_id'] != "" ? $data['corporate_customer_id'] : "";
        $trip_material = [];
        $prep_material_data = [];

        $e_ticket = \App\Model\DropoffMaterial::where('trip_id',$data['trip_id'])->value('e_ticket');
        $e_ticket_pickup = \App\Model\PickupMaterial::where('trip_id',$data['trip_id'])->value('e_ticket');

        foreach($material as $mat_data){ //For validating entered data and preparing data for SAP

            $material_data = \App\Model\CorporateCustomerMaterial::where('id',$mat_data['material_id'])->first(['parent_material_id','child_material_desc as name','child_material_code as material_code']);
           
            if(!isset($material_data) || $material_data == null){
                return response()->json([
                    "code" => 404,
                    "message" => "Material not found for material id ".$mat_data['material_id']
                ]);
            }
            $material_parent_data = Material::where('material_id',$material_data['parent_material_id'])->first(['name','material_code']);
            
            $material_parent_data['name'] = json_decode($material_parent_data['name']);
            $material_parent_data['name'] = $material_parent_data['name']->en;

            $material_data['name'] = json_decode($material_data['name']);
            $material_data['name'] = $material_data['name']->en;
            $unit = Unit::where('id',$mat_data['unit'])->value('key');
                
            $data_mat = [
                            "MaterialParentCode"=>isset($material_parent_data['material_code']) && $material_parent_data['material_code'] != null && $material_parent_data['material_code'] != "" ? (string)$material_parent_data['material_code'] : "",
                            "MaterialParentCodeName"=> isset($material_parent_data['name']) && $material_parent_data['name'] != null && $material_parent_data['name'] != "" ? $material_parent_data['name'] : "",
                            "MaterialCustomerCode"=> isset($material_data['material_code']) && $material_data['material_code'] != null && $material_data['material_code'] != "" ? (string)$material_data['material_code'] : "",
                            "MaterialCustomerCodeName"=> $material_data['name'],
                            "MaterialWeight"=> (string)$mat_data['weight'],
                            "MaterialUnit"=> isset($unit) && $unit != null && $unit != "" ? $unit : "",
                        ];
            array_push($prep_material_data,$data_mat); //preparing material array to be sent to SAP


                \App\Model\DropoffMaterial::updateOrCreate(
                                            [   'trip_id' => $data['trip_id'] , 'material_id' => $material_data['parent_material_id']],
                                            [
                                                'weight' =>  $mat_data['weight'],
                                                'unit' =>  $mat_data['unit'],
                                                'e_ticket' => $e_ticket,
                                                'status' => 1
                                            ]);
                // \App\Model\PickupMaterial::firstOrCreate(
                //                             [   'trip_id' => $data['trip_id'] , 'material_id' => $material_data['parent_material_id']],[ 'weight' =>  0]);
                
                array_push($trip_material,$material_data['parent_material_id']);

        }
        if(count($trip_material) > 0 ){
            \App\Model\DropoffMaterial::where('trip_id',$data['trip_id'])->whereNotIn('material_id',$trip_material)->update(['deleted_at' => date('Y-m-d H:i:s')]);
          }


          $driver_data = [ //preparing data for SAP
           
            "CUSTOMER_CODE"=> isset($customer_dropoff_id) && $customer_dropoff_id != null && $customer_dropoff_id != "" ? (string)$customer_dropoff_id : "",
            "TripNo"=> (string)$data['trip_id'],
            "TotalWeight"=> isset($data['total_weight']) && $data['total_weight'] != null && $data['total_weight'] != "" ? (string)$data['total_weight'] : "",
            "CustomerTicket"=> isset($data['customer_ticket']) && $data['customer_ticket'] != null && $data['customer_ticket'] != "" ? (string)$data['customer_ticket'] : "",
            "CusTicketDate"=> isset($data['customer_ticket_date']) && $data['customer_ticket_date'] != null && $data['customer_ticket_date'] != "" ? (string)$data['customer_ticket_date'] : "",
            "ReasonId"=> isset($data['reason_id']) && $data['reason_id'] != null && $data['reason_id'] != "" ? (string)$data['reason_id'] : "",
            
            "MATERIAL"=> $prep_material_data,
            "RESPONSE"=> [
                [
                    "StatusCode"=> "",
                    "Message"=> ""
                ]
            ]
        ];
        $data_sap = json_encode($driver_data);
        $sap_obj = new SapApi();
        $sap_obj->fname = "modifyTripData";
        $sap_obj->body = $data_sap;
        $sap_obj->save();

        $response = DeliveryTrip::modifyDriverDataSAP($driver_data,$sap_obj);
        $response = JSON_DECODE(JSON_DECODE($response,true),true);

        // if($response['d']['RESPONSE']['results'][1]['StatusCode'] != 201){
        //     return response()->json([   //temporarily commented
        //                 "code" => $response['d']['RESPONSE']['results'][1]['StatusCode'],
        //                 "message" => $response['d']['RESPONSE']['results'][1]['Message'] . " for trip ID ".$data['trip_id']
        //               ]);
        // }

        
      
          if(isset($customer_dropoff_id) && $customer_dropoff_id != null){
            $customer_dropoff_loc = \App\Model\Address::where('erp_id',$customer_dropoff_id)->value('address_id');
            DeliveryTrip::where('delivery_trip_id',$data['trip_id'])
                        ->update([
                                'customer_dropoff_loc_id' => $customer_dropoff_loc,
                                'posted_date' => date('Y-m-d H:i:s')
                                ]);
          }else{
            DeliveryTrip::where('delivery_trip_id',$data['trip_id'])->update(['posted_date' => date('Y-m-d H:i:s')]);
          }
          


        return response()->json([
            "code" => 200,
            "data" => [],
            "message" => "delivery trip has been posted Successfully"
        ]);
    }



}
