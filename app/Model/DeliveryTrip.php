<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Model\TripLogs as TripLogs;
use App\Validator\DeliveryTrip as Validator;
use Auth;
use DateTime;
use DateInterval;
use Carbon\Carbon;
use App\Model\OrderStatus as OrderStatus;
use App\Model\Vehicle as Vehicle;
use App\Model\OrderLogs as OrderLogs;
use App\Model\Order as Order;
use App\Model\Location as Location;
use App\Model\Constraints;
use App\Model\Store;
use DB;

use function PHPUnit\Framework\isEmpty;

class DeliveryTrip extends Model
{
    use Validator;
    protected $primaryKey = "delivery_trip_id";
    protected $table = "delivery_trips";
    public $timestamps = false;
    protected $fillable = [
        'delivery_trip_type',
        'trip_code',
        'vehicle_id',
        'opening_odometer',
        'closing_odometer',
        'delivery_trip_type',
        'erp_id',
        'trip_status_id',
        'trip_date',
        'trip_startime',
        'trip_endtime',
        'total_distance',
        'total_time',
        'suggested_path',
        'batch_no',
        'order_id',
        'store_id',
        'status',
        'posted_date',
        'created_by'
    ];
   
    function order()
    {
        return $this->belongsTo('App\Model\Order', 'order_id', 'order_id');
    }

    function store()
    {
        return $this->belongsTo('App\Model\Store', 'aqg_pickup_loc_id','store_id');
    }

    function vehicle()
    {
        return $this->belongsTo('App\Model\Vehicle', 'vehicle_id');
    }
    function constraints()
    {
        return $this->hasMany('App\Model\Constraints', 'trip_id', 'delivery_trip_id');

    }

    function trip_status()
    {
        return $this->belongsTo('App\Model\TripStatus', 'trip_status_id');
    }

    function pickup_material()
    {
        return $this->hasMany('App\Model\PickupMaterial', 'trip_id', 'delivery_trip_id')
            ->select(['id', 'trip_id', 'material_id', 'weight', 'unit', 'e_ticket', 'gate_pass']);
    }

    function dropoff_material()
    {
        return $this->hasMany('App\Model\DropoffMaterial', 'trip_id', 'delivery_trip_id')
            ->select(['id', 'trip_id', 'order_id', 'material_id', 'weight', 'unit', 'e_ticket', 'gate_pass'])
            ->orderBy('e_ticket','desc');

    }

    function customer_dropoff() {
        return $this->belongsTo('App\Model\Address', 'customer_dropoff_loc_id', 'address_id');
    }
    function aqg(){
        return $this->belongsTo('App\Model\Store', 'aqg_dropoff_loc_id', 'store_id');
    }
    function customer_pickup() {
        return $this->belongsTo('App\Model\Address', 'customer_pickup_loc_id', 'address_id');
    }
    function aqg_pickup(){
        return $this->belongsTo('App\Model\Store', 'aqg_pickup_loc_id', 'store_id');
    }



    public static function deleteDelivery($id)
    {
        $flight = DeliveryTrip::where('delivery_trip_id', $id)->delete();

        if (!$flight) {
            return false;
        }

        return true;
    }

    public static function updateDeliveryTrip($data, $user)
    {
        if (!isset($data['trip_code'])) {
            $exisitingDeliveryTrip = DeliveryTrip::where('delivery_trip_id', $data['delivery_trip_id'])->first();
        } else {
            $exisitingDeliveryTrip = DeliveryTrip::where('trip_code', $data['trip_code'])->first();
        }
        if (gettype($exisitingDeliveryTrip) === "object") {
            if ((isset($data['vehicle_id']) && ($data['vehicle_id'] != '' || !empty($data['vehicle_id'])))     &&   (isset($data['trip_date']) && ($data['trip_date'] != '' || !empty($data['trip_date'])))) {
                $checkIfAlreadyVehicelNotAvailabeOnDate = DeliveryTrip::whereDate('trip_date', '=', $data['trip_date'])->where('vehicle_id', $data['vehicle_id'])->get()->toArray();
                if (count($checkIfAlreadyVehicelNotAvailabeOnDate) > 0) {
                    return response()->json([
                        "code" => 500,
                        "error" => "",
                        "message" => "No available vehicle found on " . $data['trip_date']
                    ]);
                } else {
                    try {
                        \DB::beginTransaction();
                        $updateTrip = DeliveryTrip::where('delivery_trip_id', $exisitingDeliveryTrip->delivery_trip_id)->update(['trip_date' => $data['trip_date'], 'vehicle_id' => $data['vehicle_id']]);
                        if ($updateTrip) {
                            \DB::commit();
                            return response()->json([
                                "code" => 200,
                                "error" => "",
                                "message" => "Trip updated Successfully"
                            ]);
                        } else {
                            \DB::rollBack();
                            return response()->json([
                                "code" => 500,
                                "error" => $ex->getMessage(),
                                "message" => ""
                            ]);
                        }
                    } catch (Exception $ex) {
                        return response()->json([
                            "code" => 500,
                            "error" => $ex->getMessage(),
                            "message" => ""
                        ]);
                    }
                }
            } elseif ((isset($data['vehicle_id']) && ($data['vehicle_id'] != '' || !empty($data['vehicle_id'])))     &&   (!isset($data['trip_date']) && ($data['trip_date'] == '' || empty($data['trip_date'])))) {
                //checking for vehicle_availability on current date if date is not set in request
                $checkIfAlreadyVehicelNotAvailabeOnDate = DeliveryTrip::whereDate('trip_date', '=', date('Y-m-d'))->where('vehicle_id', $data['vehicle_id'])->get()->toArray();
                if (count($checkIfAlreadyVehicelNotAvailabeOnDate) > 0) {
                    return response()->json([
                        "code" => 500,
                        "error" => "",
                        "message" => "No available vehicle found on " . date('Y-m-d')
                    ]);
                } else {
                    try {
                        \DB::beginTransaction();
                        $updateTrip = DeliveryTrip::where('delivery_trip_id', $data['delivery_trip_id'])->update(['vehicle_id' => $data['vehicle_id']]);
                        if ($updateTrip) {
                            \DB::commit();
                            return response()->json([
                                "code" => 200,
                                "error" => "",
                                "message" => "Trip updated Successfully"
                            ]);
                        } else {
                            \DB::rollBack();
                            return response()->json([
                                "code" => 500,
                                "error" => $ex->getMessage(),
                                "message" => ""
                            ]);
                        }
                    } catch (Exception $ex) {
                        return response()->json([
                            "code" => 500,
                            "error" => $ex->getMessage(),
                            "message" => ""
                        ]);
                        //return false;
                    }
                }
            } else {
                return response()->json([
                    "code" => 500,
                    "error" => "",
                    "message" => "Error in request!"
                ]);
            }
        }
        return response()->json([
            "code" => 500,
            "error" => "",
            "message" => "No trip found!"
        ]);
    }
    public static function deleteTrip($id)
    {
        $flight = DeliveryTrip::find($id);
        if (!$flight) {
            return false;
        }
        $trip = $flight->delete();
        return $trip;
    }
    public static function createDeliveryTrip($data, $user_id, $store_id, $vehicle_id, $driver_id,$pickup_id)
    {

        $request = new DeliveryTrip();
       
        
// dd($pickup_id);


        $current_id = \DB::select("select delivery_trip_id from delivery_trips order by delivery_trip_id DESC limit 1");
        if (empty($current_id)) {
            $current_id = new DeliveryTrip();
            $converted_number = str_pad($current_id[0], 0, "0", STR_PAD_LEFT);
        } else {
            $converted_number = str_pad($current_id[0]->delivery_trip_id + 1, 0, "0", STR_PAD_LEFT);
        }
        $request->trip_code = 'CT' . $store_id . $converted_number;
        $request->delivery_trip_id = $converted_number;
        $request->trip_date = $data['trip_date'];
        $request->vehicle_id = $vehicle_id;
        $request->driver_id = $driver_id;

        $request->store_id = $store_id;
        $request->order_id = $data['order_id']['id'];
        $request->status = 1;
        $request->delivery_trip_type = 'Custom';
        $request->trip_status_id = 1;
        $request->created_by = $user_id;
        $request->trip_startime = NULL;
        $request->trip_endtime = NULL;
        $request->created_at =  date('Y-m-d H:i:s');
        $request->updated_at =  date('Y-m-d H:i:s');
        $request->batch_no = $data['batch_no'];
        if(isset($pickup_id) &&  $pickup_id != null){ //28-10-2022
            $request->aqg_pickup_loc_id = $pickup_id;
        }

        try {
            \DB::beginTransaction();
            if ($request->save()) {
                \DB::commit();
                $user = Auth::user();
                return $request;
               
            } else {
                \DB::rollBack();
                return false;
            }
        } catch (Exception $ex) {
            Error::trigger("request.add", [$ex->getMessage()]);
            return false;
        }
    }

    public static function createDeliveries($trip_id, $data, $lat, $lng, $user_id,$pickup_id,$dropoff_id)
    {
        
        $vehicle =  Vehicle::where('vehicle_id', $data['vehicle_id'])->first();

        $vehicleAvgSpeed = ($vehicle->speed == NULL) ? 60 : json_decode($vehicle->speed, true)['avg'];

        $vehicleAvgSpeed = ($vehicleAvgSpeed) / 60;
        $finalArray = array();
        $error = array();
        $total_trip_distance = 0;
        $total_trip_time = 0;
        $total_service_time = 0;
        $order_ids = [];

        //Pickup Phase
        if(isset($pickup_id) && $pickup_id != null){
            $pickup = Store::where('store_id', $pickup_id)
            ->selectRaw("longitude, latitude, opentime as open_time, closetime as close_time,location_id")->first();
            if(!isset($pickup) || $pickup == null){
                return ['code' => 500, "error" => "Address doesn't exist"];
            }
            $wlat = $pickup['latitude'];
            $wlng = $pickup['longitude'];
        }
        else{
            $pickup = Address::where('address_id', $data['order_id']['pickup_id'])
            ->select('longitude', 'latitude', 'open_time', 'close_time','location_id')->first();
            $wlat = $pickup['latitude'];
            $wlng = $pickup['longitude'];
        }
        
        $distanceOrderedArray = DeliveryTrip::calculateDistance($data, $lat, $lng, $wlat, $wlng);
        $triptime = ($distanceOrderedArray[0]['distance'] > 0) ? ($distanceOrderedArray[0]['distance'] / $vehicleAvgSpeed) : 0.00;
        $triptime = (int)$triptime;

        //GetScalesDelayTillPickup
        //Pickup Scales Constraints
        $pickupscaledelay = 0;

        if (count($data['pickup_scales']) > 0) {
            for ($i = 0; $i < count($data['pickup_scales']); $i++) {
                $pickupscaledelay += $data['pickup_scales'][$i]['delay'];
                $pickupscaledata[] = [

                    'trip_id' => $trip_id,
                    'location_id' => $data['pickup_scales'][$i]['id'],
                    'location_level_id' => 2,
                    'key' => 'PICKUP',
                    'delay' =>  $data['pickup_scales'][$i]['delay'],
                    'created_at' =>  date('Y-m-d H:i:s')


                ];
            }
            Constraints::insert($pickupscaledata);
        }
         //Pickup Cities Constraints
        if (count($data['pickup_cities']) > 0) {
            for ($i = 0; $i < count($data['pickup_cities']); $i++) {

                $pickupcitiesdata[] = [

                    'trip_id' => $trip_id,
                    'location_id' => $data['pickup_cities'][$i]['id'],
                    'location_level_id' => 1,
                    'key' => 'PICKUP',
                    'entry_time' =>  isset($data['pickup_cities'][$i]['entry_time'])?$data['pickup_cities'][$i]['entry_time']:NULL,
                    'exit_time' =>  isset($data['pickup_cities'][$i]['exit_time'])?$data['pickup_cities'][$i]['exit_time']:NULL,
                    'created_at' =>  date('Y-m-d H:i:s')


                ];
            }
            Constraints::insert($pickupcitiesdata);
        }
        

        $total_timepickup = $triptime + $pickupscaledelay;

        $pickuptriptime = gmdate("H:i:s", ($total_timepickup * 60));
        $timetillpickup = $pickuptriptime;

        // if ($pickup['location_id']!=0 || $pickup['location_id']!=null) {
        //     $citytime = DB::table('locations')->where('location_id', $pickup['location_id'])
        //         ->select('entry_time', 'exit_time')->first();
        //     $cityentry = $citytime->entry_time;
        //     $cityexit = $citytime->exit_time;
        // } else {
            $cityentry = $pickup['open_time'] != null ? $pickup['open_time'] : '01:00:00';
            $cityexit =  $pickup['close_time'] != null ? $pickup['close_time'] : '12:00:00';
        // }


        $ETA_to_pickup = $total_timepickup;

        $tripdates = $data['trip_date'] . ' ' . $cityentry;

        $time = new DateTime($tripdates);
     

        $time->sub(new DateInterval('PT' . $ETA_to_pickup . 'M'));

        $start_time = $time->format('Y-m-d H:i');

        $start_arrival = $tripdates;
      


        $cityentry = Carbon::createFromFormat('H:i:s', $cityentry);


        $pickuptriptime = Carbon::createFromFormat('H:i:s', $pickuptriptime);



        $diff = $pickuptriptime->diffInMinutes($cityentry);
        $pickuptrip_starttime = gmdate("H:i", ($diff * 60));

        $lat1 = $pickup['latitude'];
        $lng1 = $pickup['longitude'];

        //Dropoff Phase 

        $dropoff = Address::where('address_id', $dropoff_id)
            ->select('longitude', 'latitude')->first();

            if(isset($dropoff)){
                DeliveryTrip::where('delivery_trip_id',$trip_id)->update(['customer_dropoff_loc_id' => $dropoff_id]);
            }
        

        if (!isset($dropoff)) {
            $dropoff = Store::where('store_id', $dropoff_id)
                ->select('longitude', 'latitude')->first();
                if(isset($dropoff)){
                    DeliveryTrip::where('delivery_trip_id',$trip_id)->update(['aqg_dropoff_loc_id' => $dropoff_id]);
                }

        }
        if (!isset($dropoff)) {
            return ['code' => 500, "error" => "Address doesn't exist"];
        }
       

        $wlat1 =  $dropoff['latitude'];
        $wlng1 =  $dropoff['longitude'];

        $distanceArray = DeliveryTrip::calculateDistance($data, $lat1, $lng1, $wlat1, $wlng1);

        $triptime = ($distanceArray[0]['distance'] > 0) ? ($distanceArray[0]['distance'] / $vehicleAvgSpeed) : 0.00;
        $triptime = (int)$triptime;

        //GetScalesDelayTillDropoff
        //Dropoff Scales Constraints 
        $dropoffscaledelay = 0;
        if (count($data['dropoff_scales']) > 0) {
            for ($i = 0; $i < count($data['dropoff_scales']); $i++) {

                $dropoffscaledelay += $data['dropoff_scales'][$i]['delay'];
                $dropoffscaledata[] = [

                    'trip_id' => $trip_id,
                    'location_id' => $data['dropoff_scales'][$i]['id'],
                    'location_level_id' => 2,
                    'key' => 'DROPOFF',
                    'delay' =>  $data['dropoff_scales'][$i]['delay'],
                    'created_at' =>  date('Y-m-d H:i:s')


                ];
            }
            Constraints::insert($dropoffscaledata);
        }
        //Dropoff Cities Constraints

        if (count($data['dropoff_cities']) > 0) {
            for ($i = 0; $i < count($data['dropoff_cities']); $i++) {

                $dropoffcitiesdata[] = [

                    'trip_id' => $trip_id,
                    'location_id' => $data['dropoff_cities'][$i]['id'],
                    'location_level_id' => 1,
                    'key' => 'DROPOFF',
                    'entry_time' =>  isset($data['dropoff_cities'][$i]['entry_time'])?$data['dropoff_cities'][$i]['entry_time']:NULL,
                    'exit_time' => isset( $data['dropoff_cities'][$i]['exit_time'])? $data['dropoff_cities'][$i]['exit_time']:NULL,
                    'created_at' =>  date('Y-m-d H:i:s')


                ];
            }
            Constraints::insert($dropoffcitiesdata);
        }

        if($data['load_service_time']==0 || $data['load_service_time']==null)
        {
            $data['load_service_time']=60;
        }
        if($data['dropoff_service_time']== 0 || $data['dropoff_service_time']==null)
        {
            $data['dropoff_service_time'] = 60;
        }

        $total_timedropoff = $triptime + $dropoffscaledelay;

        $ETA_to_dropoff = $data['load_service_time'] + $total_timedropoff;

        $drop_arrival = new DateTime($start_arrival);


        $drop_arrival->add(new DateInterval('PT' . $ETA_to_dropoff . 'M'));



        $droptriptime = gmdate("H:i:s", ($total_timedropoff * 60));

        $timetilldropoff = $droptriptime;

        $dropoff_time = $drop_arrival->format('Y-m-d H:i');

        $unload_st = $data['dropoff_service_time'];

        $endtime = new DateTime($dropoff_time);

        $endtime->add(new DateInterval('PT' . $data['dropoff_service_time'] . 'M'));


        $endtimeplanned = $endtime->format('Y-m-d H:i');

        $cityexit = Carbon::createFromFormat('H:i:s', $cityexit);

        $droptriptime = Carbon::createFromFormat('H:i:s', $droptriptime);

        $diff = $droptriptime->diffInMinutes($cityexit);


        $droptrip_starttime = gmdate("H:i:s", ($diff * 60));

        $servicetime = $data['load_service_time'] + $data['dropoff_service_time'];
        $totaltriptime = $total_timepickup + $total_timedropoff + $servicetime;
        $totaltriptime = gmdate("H:i", ($totaltriptime * 60));
        $totaltripdistance = $distanceArray[0]['distance'] + $distanceOrderedArray[0]['distance'];

        $secs = strtotime($pickuptrip_starttime) - strtotime("00:00");

        $mytime = Carbon::now();
        $mytime->toDateTimeString();

        $trip_start = Carbon::createFromFormat('Y-m-d H:i', $start_time);
        if($trip_start->gte($mytime)){
         
        }
        else {

            $timediff = $mytime->diffInMinutes($trip_start);

            $start_time=new DateTime($start_time);
            $start_time->add(new DateInterval('PT' . $timediff . 'M'));
            $start_time = $start_time->format('Y-m-d H:i');

            $endtimeplanned=new DateTime($endtimeplanned);
            $endtimeplanned->add(new DateInterval('PT' . $timediff . 'M'));
            $endtimeplanned = $endtimeplanned->format('Y-m-d H:i');

            $start_arrival=new DateTime($start_arrival);
            $start_arrival->add(new DateInterval('PT' . $timediff . 'M'));
            $start_arrival = $start_arrival->format('Y-m-d H:i');

            $dropoff_time=new DateTime($dropoff_time);
            $dropoff_time->add(new DateInterval('PT' . $timediff . 'M'));
            $dropoff_time = $dropoff_time->format('Y-m-d H:i');
        
        }
        if($data['load_service_time']==0 || $data['load_service_time']==null)
        {
            $data['load_service_time']=60;
        }
        if($data['dropoff_service_time']== 0 || $data['dropoff_service_time']==null)
        {
            $data['dropoff_service_time'] = 60;
        }
      
        $hours = floor($data['load_service_time'] / 60);
        $minutes = $data['load_service_time'] % 60;
        
        $dropoff_hours = floor($data['dropoff_service_time'] / 60);
        $dropoff_minutes = $data['dropoff_service_time'] % 60;
        
        $data['load_service_time'] = $hours .":".$minutes;
        $data['dropoff_service_time'] = $dropoff_hours .":".$dropoff_minutes;

        $tripdata = [

            'start_time_planned' => $start_time,
            'end_time_planned' => $endtimeplanned,
            'pickup_distance' => $distanceOrderedArray[0]['distance'],
            'pickup_time' => $start_arrival,
            'dropoff_distance' => $distanceArray[0]['distance'],
            'dropoff_time' => $dropoff_time,
            'total_time' => $totaltriptime,
            'total_distance' => $totaltripdistance,
            'pickup_service_time' => $data['load_service_time'],
            'dropoff_service_time' => $data['dropoff_service_time']


        ];

        //Estimated Columns Information
        // 'start_time_planned' => Estimated time trip should be started
        // 'end_time_planned' => Estimated time trip should be ended
        // 'pickup_distance' => distance from start point to pickup point
        // 'pickup_time' => estimated time vehicle will reach pickup spot,
        // 'dropoff_distance' => estimated distance from pickup point to ripoff point,
        // 'dropoff_time' => estimated time from pickup point to drop-off point,
        // 'total_time' => estimated total time of the trip,
        // 'total_distance' => estimated total distance of the trip,
        // 'pickup_service_time' => estimated service time during pickup
        // 'dropoff_service_time' => estimated pickup time during dropoff

        //Calculated Columns Information
        // 'trip_starttime  ' => Actual time trip is started
        // 'trip_endtime' => Actual time trip is ended
        // 'actual_time' => Actual total time of the trip,
        // 'actual_distance' => Actual total distance of the trip,
        // 'actual_pstime' => Actual service time during pickup
        // 'actual_dstime' => Actual service time during dropoff
        // 'load' => Actual time material is loaded
        // 'unload' => Actual time material is unloaded




        $updatedeliverytrip = \DB::table("delivery_trips")->where('delivery_trip_id', $trip_id)->update($tripdata);
        $vehicle =  Vehicle::where('vehicle_id', $data['vehicle_id'])->update(['job_order' => $trip_id]);

        $logdata[] = [

            'trip_id' => $trip_id,
            'order_id' => $data['order_id']['id'],
            'trip_status_id' => 1,
            'created_at' =>  date('Y-m-d H:i:s'),
            'updated_at' =>  date('Y-m-d H:i:s')

        ];
        TripLogs::insert($logdata);

        return array('code' => 200, 'error' => $error, 'data' => $tripdata);
    }

    public static function calculateDistance($data, $lat, $lng, $wlat, $wlng)
    {
        $orderDistances = array();
        $orders = $data['order_id'];
        $base_lat = $wlat;
        $base_lng = $wlng;



        if ($lat == '' || $lng == '' || $wlat == '0' || $wlng == '0') {
            $distance = 0;
        } else {

            $distance = DeliveryTrip::distance($lat, $lng, $base_lat, $base_lng);
        }

        $orderDistances[] = [
            "id" => $data['order_id']['id'],
            "lat" => $lat,
            "lng" => $lng,
            "distance" => round($distance, 2)
        ];

        usort($orderDistances, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
        return $orderDistances;
    }

    public static function distance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371)
    {
        if (($latitudeFrom == $longitudeFrom) && ($latitudeTo == $longitudeTo)) {
            return 0;
        } else {

            // convert from degrees to radians


            // $latFrom = deg2rad($latitudeFrom);
            // $lonFrom = deg2rad($longitudeFrom);
            // $latTo = deg2rad($latitudeTo);
            // $lonTo = deg2rad($longitudeTo);
            // $latDelta = $latTo - $latFrom;
            // $lonDelta = $lonTo - $lonFrom;
            // $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            //     cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            // $c = $angle * $earthRadius;
            // $z = $c * 1.57;
            // dd($z);


            $z=   \DB::select("SELECT( 3959 * acos( cos( radians($latitudeTo) ) * cos( radians( $latitudeFrom ) ) 
            * cos( radians( $longitudeFrom ) - radians($longitudeTo) ) + sin( radians($latitudeTo) ) 
            * sin( radians( $latitudeFrom ) ) ) ) as distance");

            $count=count($z);
            if($count > 0)
            {
                $z=$z[0]->distance;
            }
   
         return round($z, 2);
        }
    }


    public function sendTripDataToSAP($data,$sap_obj){
        $errors = [];
        $code = 201; $message = "Fleet Asset has been added successfully.";
        // $data = $request->all();//$request->all();
        // $request_log_id = $data['request_log_id'];
        // unset($data['request_log_id']);
        $credentials = base64_encode('cwauser:init12345');
        $headers = [
            'Cookie' => 'SAP_SESSIONID_AGD_550=gwmSzEf2OkiqrsOpRKRT91_gpgksOBHsnhoAUFaNKsA%3d; sap-usercontext=sap-client=550',
            'Authorization' => 'Basic '. $credentials,
            'content-type' => 'application/json',
            'Accept' => 'application/json',
            'X-Requested-With' => 'X'
        ];
        $method = "POST"; $body = [
            "d" => $data 
            ]; 
            
            $url = 'http://88.85.251.150:8001/sap/opu/odata/sap/ZAQI_CWA_SALES_SRV/AQGNOW_REFERENCESet';
            
        // $url.='&$filter=(AssetCode eq \'Q3-184\')'; // for first record fetching // Q3/495 for sample
        $response = callExternalAPI($method,$url,$body,$headers);
       
        $sap_obj->request = $url;
        $sap_obj->response = $response;
        $sap_obj->save();

        return $response;

    }

    public function loadMaterialSAP($delivery_trip_id,$status_code,$sap_obj){
        $errors = [];
        $code = 201; $message = "Fleet Asset has been added successfully.";
        // $data = $request->all();//$request->all();

        $headers = [
            'Cookie' => 'SAP_SESSIONID_AGD_550=gwmSzEf2OkiqrsOpRKRT91_gpgksOBHsnhoAUFaNKsA%3d; sap-usercontext=sap-client=550',
            'Authorization' => 'Basic Y3dhdXNlcjppbml0MTIzNDU=',
        ];
        $method = "GET"; $body = array(); 
        $url = "http://88.85.251.150:8001/sap/opu/odata/sap/ZAQI_CWA_SALES_SRV/AQGNOW_CONFIRMLOADSet(AqgnowRef='".$delivery_trip_id."',StatusCode='$status_code')";

        $params = [
            'query' => [
               '$expand' => "AQGNOW_MATERIALSSet",
               '$format' => "json"
            ]
         ];
        $response = callExternalAPI($method,$url,$body,$headers,$params);
        $response = json_encode($response);

        $sap_data = [
            'request' => $url,
            'response' => $response,
        ];
        $sap_obj->request = $url;
        $sap_obj->response = $response;
        $sap_obj->save();
       
        return $response;
    }


    public function confirmLoadDriverSAP($data,$sap_obj){
        $errors = [];
        $code = 201; $message = "Fleet Asset has been added successfully.";
        // $data = $request->all();//$request->all();
        // $request_log_id = $data['request_log_id'];
        // unset($data['request_log_id']);
        $credentials = base64_encode('cwauser:init12345');
        $headers = [
            'Cookie' => 'SAP_SESSIONID_AGD_550=gwmSzEf2OkiqrsOpRKRT91_gpgksOBHsnhoAUFaNKsA%3d; sap-usercontext=sap-client=550',
            'Authorization' => 'Basic '. $credentials,
            'content-type' => 'application/json',
            'Accept' => 'application/json',
            'X-Requested-With' => 'X'
        ];
        $method = "POST"; $body = [
            "d" => $data 
            ]; 
            
            $url = 'http://88.85.251.150:8001/sap/opu/odata/SAP/ZAQI_CWA_SALES_SRV/CONFIRM_LOADSet';
            
        // $url.='&$filter=(AssetCode eq \'Q3-184\')'; // for first record fetching // Q3/495 for sample
        $response = callExternalAPI($method,$url,$body,$headers);
        // $response = json_encode($response);
        $sap_data = [
            'request' => $url,
            'response' => $response,
        ];
        $sap_obj->request = $url;
        $sap_obj->response = $response;
        $sap_obj->save();
        return $response;
        
    }

    public function confirmUnLoadDriverSAP($data,$sap_obj){
        $errors = [];
        $code = 201; $message = "Fleet Asset has been added successfully.";
        // $data = $request->all();//$request->all();
        // $request_log_id = $data['request_log_id'];
        // unset($data['request_log_id']);
        $credentials = base64_encode('cwauser:init12345');
        $headers = [
            'Cookie' => 'SAP_SESSIONID_AGD_550=gwmSzEf2OkiqrsOpRKRT91_gpgksOBHsnhoAUFaNKsA%3d; sap-usercontext=sap-client=550',
            'Authorization' => 'Basic '. $credentials,
            'content-type' => 'application/json',
            'Accept' => 'application/json',
            'X-Requested-With' => 'X'
        ];
        $method = "POST"; $body = [
            "d" => $data 
            ]; 
            
            $url = 'http://88.85.251.150:8001/sap/opu/odata/SAP/ZAQI_CWA_SALES_SRV/UPDATE_TRIPSet';
            
        // $url.='&$filter=(AssetCode eq \'Q3-184\')'; // for first record fetching // Q3/495 for sample
        $response = callExternalAPIwithContent($method,$url,$body,$headers);
       
        $sap_obj->request = $url;
        $sap_obj->response = $response;
        $sap_obj->save();
        return $response;
        
    }

    public function modifyDriverDataSAP($data,$sap_obj){
        $errors = [];
        $code = 201; $message = "";
        // $data = $request->all();//$request->all();
        // $request_log_id = $data['request_log_id'];
        // unset($data['request_log_id']);
        $credentials = base64_encode('cwauser:init12345');
        $headers = [
            'Cookie' => 'SAP_SESSIONID_AGD_550=gwmSzEf2OkiqrsOpRKRT91_gpgksOBHsnhoAUFaNKsA%3d; sap-usercontext=sap-client=550',
            'Authorization' => 'Basic '. $credentials,
            'content-type' => 'application/json',
            'Accept' => 'application/json',
            'X-Requested-With' => 'X'
        ];
        $method = "POST"; $body = [
            "d" => $data 
            ]; 
            
            $url = 'http://88.85.251.150:8001/sap/opu/odata/SAP/ZAQI_CWA_SALES_SRV/SUPERVISOR_CONFIRM_LOADSet';
            
        // $url.='&$filter=(AssetCode eq \'Q3-184\')'; // for first record fetching // Q3/495 for sample
        $response = callExternalAPI($method,$url,$body,$headers);
        // $response = json_encode($response);
        $sap_data = [
            'request' => $url,
            'response' => $response,
        ];
        
        $sap_obj->request = $url;
        $sap_obj->response = $response;
        $sap_obj->save();
        return $response;
        
    }

   

}
