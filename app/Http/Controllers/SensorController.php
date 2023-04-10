<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Sensor;
use App\Model\Vehicle as Vehicle;
use DB;
use Validator;
class SensorController extends Controller

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
    *   path="/sensors",
    *   summary="Return the list of sensor",
    *   tags={"sensors"},
    *    @OA\Response(
    *      response=200,
    *      description="List of sensors",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of sensors",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */

    public function index() {
        $sensors = Sensor::orderBy('created_at','DESC')->get();
        //get list of sensor types
        $sensor_types = \App\Model\SensorType::orderBy('created_at','DESC')->get();
        //get list of parameters
        $parameters = \App\Model\Parameter::orderBy('created_at','DESC')->get();


        return response()->json([
            "data" => $sensors,
            "sensor_types" => $sensor_types,
            "parameters" => $parameters
        ]);
    }

    public function show($sensorId) {
        $validator = Validator::make([
                    
            'sensor_id' => $sensorId
        ],[
            'sensor_id' => 'nullable|int|min:1|exists:fm_sensors,sensor_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $sensor = Sensor::find($sensorId);

        return response()->json([
            "sensor" => $sensor
        ]);
    }

    /**
    * @OA\Post(
    *   path="/sensor/add",
    *   summary="Add new sensor",
    *   operationId="create",
    *   tags={"sensor"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Sensor has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $sensor = new Sensor();

        //print_r($data); exit;
        $sensor = $sensor->add($data);

        $errors = \App\Message\Error::get('sensor.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "id" => $sensor->sensor_id,
            "message" => 'New Sensor has been created.'
        ]);
    }

    public function change(Request $request, $sensorId) {
        $validator = Validator::make([
                    
            'sensor_id' => $sensorId
        ],[
            'sensor_id' => 'nullable|int|min:1|exists:fm_sensors,sensor_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();

            $sensor = new Sensor();

            $sensor = $sensor->change($data, $sensorId);

            if (!is_object($sensor)) {
                $errors = \App\Message\Error::get('sensor.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Sensor has been updated successfully."
            ]);
        }
    }

    public function remove(Request $request, $sensorId)
    {
        $validator = Validator::make([
                    
            'sensor_id' => $sensorId
        ],[
            'sensor_id' => 'nullable|int|min:1|exists:fm_sensors,sensor_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $sensor = Sensor::find($sensorId);

        if (!is_object($sensor)) {
            return response()->json([
                "code" => 400,
                "message" => "Sensor not found."
            ]);
        }

        if ($sensor->status == 1) {
            $sensor->status = 9;
        } else {
            $sensor->status = 1;
        }
        $sensor->save();
        $sensor->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Sensor has been deleted.'
        ]);
    }

    public function getTrackerData(Request $request,$store_id,$vehicle_id) {

        $dates = json_decode($request->get("data"),true);
     
        
        if($dates != NULL || $dates != null){
            
            $validator = Validator::make([
                
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'store_id' => $store_id,
                'vehicle_id' => $vehicle_id
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'store_id' => 'required|int|min:1|exists:stores,store_id',
            'vehicle_id' => 'required|int|min:1|exists:vehicles,vehicle_id'
        ]);
            if ($validator->fails()) {
                return responseValidationError('Fields Validation Failed.', $validator->errors());
            }
        }
        // return responseValidationError('Fields Validation Failed.', $validator->errors());
    // }
      
    $dateFrom=$dates['date_from']." 00:00:00";;
    $dateTo=$dates['date_to']." 23:59:59";

        $getTrackingData = \DB::select("SELECT s.IMEI,Ignition,Movement,`GSM_Signal` as GSMSignal,Movement,s.Speed,sr.RFID,iButton,
        `Total_Odometer` as TotalOdometer,`Trip_Odometer` as TripOdometer,Latitude,Longitude,Altitude,`Battery_Level` as BatteryLevel,Satellites,fs.title as sensortitle,fs.icon as sensoricon,fs.current_state as sensorcurrentstate,fs.current_value,fs.current_state,
        fs.status as sensorstatus,fp.status parameterstatus,`DateTime`,sr.address
         FROM sensor_data s
        INNER JOIN sensor_data_rare sr ON s.rare_id=sr.rare_id
        inner join fm_devices fm on s.IMEI = fm.imei
        inner join vehicles v on fm.device_id = v.device_id 
        left join fm_sensors fs on v.device_id = fs.device_id
        left join fm_parameters fp on fs.parameter_id = fp.parameter_id 
        where (date(s.DateTime) BETWEEN  ('$dateFrom') AND ('$dateTo'))
        and v.vehicle_id = $vehicle_id
        and Ignition = 1
        group by DateTime
        order by DateTime"); 




   

$getSensorData = \DB::select("SELECT fs.title,sensor_1_text,current_value,current_state,fs.status,fs.color,fs.icon
 FROM vehicles v
inner join fm_sensors fs on v.device_id = fs.device_id
where v.vehicle_id = $vehicle_id"); 

$vehilcePlate=Vehicle::where('vehicle_id',$vehicle_id)->value('vehicle_plate_number');

        return response()->json([
            "code" => 200,
            "trackingdata" => $getTrackingData,
            "sensordata" => $getSensorData,
            "vehicleplate" => $vehilcePlate,
          
        ]);
    }

    public function getMonitoringData() {

        $getMonitoringData = \DB::select("SELECT DISTINCT (s.IMEI),Ignition,Movement,`GSM Signal`,Movement,s.Speed,RFID,iButton,
        `Total Odometer`,`Trip Odometer`,Latitude,Longitude,Altitude,`Battery Level`,Satellites FROM sensor_data s
        inner join fm_devices fm on s.IMEI = fm.imei
        inner join vehicles v on fm.device_id = v.device_id 
        order by IMEI DESC");   



        return response()->json([
            "code" => 200,
            "data" => $getMonitoringData,
          
        ]);
    }

    public function getSensorMessages(Request $request) {
        $data =  json_decode($request->getContent(),true);
    
        if($data != NULL || $data != null){
            
            $validator = Validator::make([
                
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'option' => $data['option'],
                'vehicle_id' => $data['vehicle_id'],
        
            ],[
            'date_from' => 'nullable|date|min:1',
            'date_to' => 'nullable|date|min:1',
            'option' => 'required|string|min:1',
            'vehicle_id' => 'required|int|min:1|exists:vehicles,vehicle_id'
         
        ]);
    }else{
        return responseValidationError('Fields Validation Failed.', $validator->errors());
    }
    

    $dateFrom=$data['date_from']." 00:00:00";;
    $dateTo=$data['date_to']." 23:59:59";
    $vehicle_id=$data['vehicle_id'];




    // \DB::enableQueryLog();

  
        $getData = \DB::select("SELECT td.created_at,td.data,0 AS Weight, 'OFF' AS ACC FROM tracker_data td INNER JOIN fm_devices fmd ON td.imei=concat('000f',HEX(CONVERT(fmd.imei USING latin1))) INNER JOIN vehicles v ON fmd.device_id = v.device_id WHERE (td.created_at BETWEEN  ('$dateFrom') AND ('$dateTo')) AND v.vehicle_id = $vehicle_id AND td.cleaned=1"); 

            // dd(\DB::getQueryLog(),$getData);



$getData = json_decode(json_encode($getData), true);


  
        return response()->json([
            "code" => 200,
            "data" => $getData
          
        ]);
    }



    public function setArea(Request $request, $limit) {


        $validator = Validator::make([
            
            
            'limit' => $limit
        ],[
      
        'limit' => 'required|int|min:1'
    ]);

    if ($validator->fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
   }
   
        
        $getTrackingData = \DB::select("SELECT Latitude,Longitude,address,IMEI
         FROM sensor_data s
        where s.address is NULL limit $limit"); 
        
        if(count($getTrackingData)==0)
        {
            return response()->json([
                "code" => 200,
                "message" => 'Nothing To Update',
         
            ]);

        }
for($i=0;$i<count($getTrackingData);$i++)
{
$latitude[$i]=$getTrackingData[$i]->Latitude;

$longitude[$i]=$getTrackingData[$i]->Longitude;

// $url="https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=$latitude[$i]&longitude=$longitude[$i]&localityLanguage=en'";
$url="https://nominatim.openstreetmap.org/reverse?format=json&lon=$longitude[$i]&lat=$latitude[$i]&language=en";
$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
$result=json_decode($result);


$getTrackingData[$i]->address=$result->display_name;

                     $updateDeliveryTrip = DB::table('sensor_data')->where('IMEI',$getTrackingData[$i]->IMEI)
                     ->update(['address'=> $getTrackingData[$i]->address]);

}




   


        return response()->json([
            "code" => 200,
            "message" => 'Address Updated Successfully',
     
        ]);
    }

}
