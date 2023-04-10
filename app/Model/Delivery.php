<?php

namespace App\Model;

use App\Model;
use Carbon\Carbon;
use App\Message\Error;
use App\Model\OrderStatus as OrderStatus;
use App\Model\Vehicle as Vehicle;
use App\Model\OrderLogs as OrderLogs;
use App\Model\Order as Order;
use App\Model\TripLogs as TripLogs;
use App\Model\Location as Location;
use App\Validator\Delivery as Validator;
use DB;

use function PHPUnit\Framework\isEmpty;

class Delivery extends Model
{
    use Validator;

    protected $primaryKey = "delivery_id";
    protected $table = "deliveries";
    public $timestamps = false;
    protected $fillable = [
        'delivery_trip_id',
        'order_id',
        'delivery_point',
        'delivery_date',
        'status',
        'delivery_meta',
        'created_by',
        'items',
        'distance_from_last_point'
    ];

    function delivery_trip()
    {
        return $this->belongsTo('App\Model\DeliveryTrip', 'delivery_trip_id');
    }

    function order()
    {
        return $this->belongsTo('App\Model\Order', 'order_id');
    }

    public static function deleteDelivery($id)
    {
        $flight = Delivery::where('delivery_trip_id', $id)->delete();

        if (!$flight) {
            return false;
        }

        return true;
    }


    public static function createDeliveries($trip_id, $data, $lat, $lng, $user_id,$dropoff_id)
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
        $pickup = Address::where('address_id', $data['order_id']['pickup_id'])
            ->select('longitude', 'latitude', 'open_time', 'close_time')->first();
        $wlat = $pickup['latitude'];
        $wlng = $pickup['longitude'];

        $distanceOrderedArray = Delivery::calculateDistance($data, $lat, $lng, $wlat, $wlng);

        $triptime = ($distanceOrderedArray[0]['distance'] > 0) ? ($distanceOrderedArray[0]['distance'] / $vehicleAvgSpeed) : 0.00;
        $triptime = (int)$triptime;

        //GetScalesDelayTillPickup
        $pickupscaledelay = 0;
        if (count($data['pickup_scales']) > 0) {
            for ($i = 0; $i < count($data['pickup_scales']); $i++) {
                $getdelay = Location::where('location_id', $data['pickup_scales'][$i])->pluck('delay')->first();
                $pickupscaledelay += $getdelay;
            }
        }



        $total_timepickup = $triptime + $pickupscaledelay;

        $pickuptriptime = gmdate("H:i:s", ($total_timepickup * 60));
        $timetillpickup = $pickuptriptime;

        if (!isEmpty($data['dest_city'])) {
            $citytime = DB::table('locations')->where('location_id', $data['dest_city'])
                ->select('entry_time', 'exit_time')->first();
            $cityentry = $citytime->entry_time;
            $cityexit = $citytime->exit_time;
        } else {

            $cityentry = $pickup['open_time'];
            $cityexit =  $pickup['close_time'];
        }


        $cityentry = Carbon::createFromFormat('H:i:s', $cityentry);


        $pickuptriptime = Carbon::createFromFormat('H:i:s', $pickuptriptime);

        $diff = $pickuptriptime->diffInMinutes($cityentry);
        $pickuptrip_starttime = gmdate("H:i:s", ($diff * 60));

        $lat1 = $pickup['latitude'];
        $lng1 = $pickup['longitude'];


        $dropoff = Address::where('address_id', $dropoff_id)
            ->select('longitude', 'latitude')->first();

        if ($dropoff == NULL || $dropoff['latitude'] == NULL || $dropoff['longitude'] == NULL) {
            $dropoff = Store::where('store_id', $dropoff_id)
                ->select('longitude', 'latitude')->first();
        }
        

        $wlat1 =  $dropoff['latitude'];
        $wlng1 =  $dropoff['longitude'];

        $distanceArray = Delivery::calculateDistance($data, $lat1, $lng1, $wlat1, $wlng1);

        $triptime = ($distanceArray[0]['distance'] > 0) ? ($distanceArray[0]['distance'] / $vehicleAvgSpeed) : 0.00;
        $triptime = (int)$triptime;

        //GetScalesDelayTillDropoff
        $dropoffscaledelay = 0;
        if (count($data['pickup_scales']) > 0) {
            for ($i = 0; $i < count($data['pickup_scales']); $i++) {
                $getdropoffdelay = Location::where('location_id', $data['dropoff_scales'][$i])->pluck('delay')->first();
                $dropoffscaledelay += $getdropoffdelay;
            }
        }




        $total_timedropoff = $triptime + $dropoffscaledelay;
        $droptriptime = gmdate("H:i:s", ($total_timedropoff * 60));
        $timetilldropoff = $droptriptime;



        $cityexit = Carbon::createFromFormat('H:i:s', $cityexit);

        $droptriptime = Carbon::createFromFormat('H:i:s', $droptriptime);

        $diff = $droptriptime->diffInMinutes($cityexit);

        $droptrip_starttime = gmdate("H:i:s", ($diff * 60));

        $servicetime = $data['load_service_time'] + $data['dropoff_service_time'];
        $totaltriptime = $total_timepickup + $total_timedropoff + $servicetime;
        $totaltriptime = gmdate("H:i:s", ($totaltriptime * 60));
        $totaltripdistance = $distanceArray[0]['distance'] + $distanceOrderedArray[0]['distance'];

        $secs = strtotime($pickuptrip_starttime) - strtotime("00:00:00");
        $plannedendtime = date("H:i:s", strtotime($totaltriptime) + $secs);



        $tripdata = [

            'start_time_planned' => $pickuptrip_starttime,
            'end_time_planned' => $plannedendtime,
            'pickup_distance' => $distanceOrderedArray[0]['distance'],
            'pickup_time' => $timetillpickup,
            'dropoff_distance' => $distanceArray[0]['distance'],
            'dropoff_time' => $timetilldropoff,
            'total_time' => $totaltriptime,
            'total_distance' => $totaltripdistance,


        ];

        $updatedeliverytrip = \DB::table("delivery_trips")->where('delivery_trip_id', $trip_id)->update($tripdata);

        $logdata[] = [

            'trip_id' => $trip_id,
            'order_id' => $data['order_id']['id'],
            'trip_status_id' => 1,
            'created_at' =>  date('Y-m-d H:i:s')


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

            $distance = Delivery::distance($lat, $lng, $base_lat, $base_lng);
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
        if (($latitudeFrom == $latitudeFrom) && ($latitudeTo == $longitudeTo)) {
            return 0;
        } else {


            // convert from degrees to radians

            $latFrom = deg2rad($latitudeFrom);
            $lonFrom = deg2rad($longitudeFrom);
            $latTo = deg2rad($latitudeTo);
            $lonTo = deg2rad($longitudeTo);
            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;
            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            $c = $angle * $earthRadius;
            $z = $c * 1.57;


            return round($z, 2);
        }
    }
}
