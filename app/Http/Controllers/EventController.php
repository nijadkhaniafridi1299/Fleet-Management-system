<?php

namespace App\Http\Controllers;

use App\Events\GPSAntennaCutEvent;
use App\Events\IdleEvent;
use App\Events\JobOrderEvent;
use App\Events\LiveLocationEvent;
use App\Events\LowBatteryEvent;
use App\Events\LowDCEvent;
use App\Events\MovingEngineOffEvent;
use App\Events\NoConnectionEvent;
use App\Events\OverSpeedEvent;
use App\Events\OverSpeedGoogleEvent;
use App\Events\PowercutEvent;
use App\Events\SensorEvent;
use App\Events\ServiceEvent;
use App\Events\StopEvent;
use App\Events\SuddenAccelerationEvent;
use App\Events\SuddenBrakingEvent;
use App\Events\SuddenDriftingEvent;
use App\Events\UnderSpeedEvent;
use App\Events\ZoneInEvent;
use App\Events\ZoneOutEvent;
use App\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Model\Event;
use Validator;
use Illuminate\Validation\Rule;

class EventController extends Controller

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
    *   path="/events",
    *   summary="Return the list of Events",
    *   tags={"events"},
    *    @OA\Response(
    *      response=200,
    *      description="List of events",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of events",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */




    public function liveMonitoring(Request $request){

        $message='Socket Connection is Successfull';

        event(new LiveLocationEvent($message));
    
        return response()->json(["status"=>"ok"]);
    }

    public function callEvent(Request $request) {
        
   
        $data =  json_decode($request->getContent());
        // dd($data);
       
        $data->{"email"} = 'saadabbasi263@gmail.com';
    

        if ($data != NULL || $data != null) {
            
            $validator = Validator::make([
                
                'event_type_id' => $data->event_type_id,
                'vehicle_id' =>  $data->vehicle_id,
                'event_status' =>  $data->event_status
              
            ],[
                'vehicle_id' => 'required|min:1|exists:vehicles,vehicle_id',
                // 'event_type_id' => 'required|int|min:1|exists:fm_events,event_type_id',
                'event_status' => 'required|int'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => '300',
                'message' => 'Data Missing!'
            ]);
        }
        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        if ($data->event_type_id==1)
        {
            
            event(new MovingEngineOffEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'
            ]);
        }
        else if($data->event_type_id==2)
        {
        
            event(new PowercutEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'
            ]);

        }
        else if($data->event_type_id==3)
        {
        
            event(new GPSAntennaCutEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'
                ]);

        }
        
        else if($data->event_type_id==4)
        {
        
            event(new LowDCEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'
                ]);

        }
        else if($data->event_type_id==5)
        {
        
            event(new LowBatteryEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'
                ]);

        }
        else if($data->event_type_id==6)
        {
        
            event(new NoConnectionEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);


        }      
            
        else if($data->event_type_id==7)
        {
        
            event(new OverSpeedEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);


        }
        else if($data->event_type_id==8)
        {
        
            event(new OverSpeedGoogleEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }      
        else if($data->event_type_id==9)
        {
        
            event(new UnderSpeedEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }    
        else if($data->event_type_id==10)
        {
        
            event(new SuddenAccelerationEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }   
        else if($data->event_type_id==11)
        {
        
            event(new SuddenBrakingEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }  
        else if($data->event_type_id==12)
        {
        
            event(new SuddenDriftingEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }  
        else if($data->event_type_id==13)
        {
        
            event(new SensorEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }
        else if($data->event_type_id==14)
        {
        
            event(new ServiceEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }    
        else if($data->event_type_id==15)
        {
        
            event(new ZoneInEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }  
        else if($data->event_type_id==16)
        {
        
            event(new ZoneOutEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }  
        else if($data->event_type_id==17)
        {
        
            event(new StopEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }  
        else if($data->event_type_id==18)
        {
        
            event(new IdleEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }  
        else if($data->event_type_id==19)
        {
        
            event(new JobOrderEvent($data));
            return response([
                'message'=>'Event Triggered Successfully'  
            ]);

        }  

  
    }

    public function index(Request $request) {
        $data =  $request->all();
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;
        
        $events = Event::with('eventType', 'vehicleGroups', 'vehicles', 'activeOnDays')->orderBy('event_id','DESC');
        if(isset($data['event_type']) && $data['event_type'] != ""){
            $event_type = $data['event_type'];
            $events->whereHas('eventType', function($query) use($event_type){
                $query->whereRaw('JSON_EXTRACT(LOWER(title), "$.en") LIKE "%'.trim(strtolower($event_type)).'%"')
                ->orWhereRaw('JSON_EXTRACT(LOWER(title), "$.ar") LIKE "%'.trim(strtolower($event_type)).'%"') ; 
                // $query->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($event_type)).'%']);
            });
        }
        if(isset($data['title']) && $data['title'] != ""){
            $events->whereRaw('JSON_EXTRACT(LOWER(title), "$.en") LIKE "%'.trim(strtolower($data['title'])).'%"')
            ->orWhereRaw('JSON_EXTRACT(LOWER(title), "$.ar") LIKE "%'.trim(strtolower($data['title'])).'%"') ; 
            // $events->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $events->where('status', $data['status']);
        }
        if(isset($data['notification_type']) && $data['notification_type'] != ""){
            if($data['notification_type'] == "system"){
                $events->where('system_message',1);  
            }
            elseif($data['notification_type'] == "email"){
                $events->where('send_to_emails',1);  
            }
            elseif($data['notification_type'] == "sms"){
                $events->where('send_sms_to_numbers',1);  
            }
        }
        $events = $events->paginate($data['perPage']);
        $event_types = \App\Model\EventType::where('status', 1)->orderBy('created_at','DESC')->get();
        $vehicle_groups = \App\Model\VehicleGroup::where('status', 1)->orderBy('created_at','DESC')->get();
        $vehicles = \App\Model\Vehicle::select('vehicle_id', 'vehicle_plate_number')->where('status', 1)->orderBy('created_at','DESC')->get()->toArray();
        $statuses = \App\Model\Status::where('status', 1)->orderBy('created_at','DESC')->get();
        $days =\App\Model\Day::orderBy('created_at','DESC')->get();
        $users = \App\Model\User::orderBy('created_at', 'DESC')->get()->toArray();
        $user_groups = \App\Model\Group::where('status', 1)->orderBy('created_at', 'DESC')->get()->toArray();

        return response()->json([
            "code" => 200,
            "data" => $events,
            "event_types" => $event_types,
            "vehicle_groups" => $vehicle_groups,
            "vehicles" => $vehicles,
            "statuses" => $statuses,
            "days" => $days,
            "users" => $users,
            "user_groups" => $user_groups
        ]);
    }

    public function show($eventId) {
        $validator = Validator::make([    
            'event_id' => $eventId
        ],[
            'event_id' => 'nullable|int|min:1|exists:fm_events,event_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $event = Event::find($eventId);

        return response()->json([
            "event" => $event
        ]);
    }

    /**
    * @OA\Post(
    *   path="/event/add",
    *   summary="Add new event",
    *   operationId="create",
    *   tags={"events"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New event is inserted in database",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $event = new Event();
        $event = $event->add($data['event']);
 
        if (!is_object($event)) {
            $errors = \App\Message\Error::get('event.add');
            return response()->json([
                "code" => 500,
                "errors" => $errors
            ]);
        }

        if (isset($data['active_on_days'])) {
            $eventActiveOnDay = new \App\Model\EventActiveOnDay();
            $eventActiveOnDay->activateDaystForEvent($data['active_on_days'], $event->event_id);

            $errors = \App\Message\Error::get('eventactiveonday.add');

            if (isset($errors) && count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }
        }
        
        if (isset($data['vehicle_groups']) && count($data['vehicle_groups']) > 0) {
            $vehicleGroupsInEvent  = new \App\Model\VehicleGroupsInEvent();
            $vehicleGroupsInEvent->addVehicleGroupsInEvent($data['vehicle_groups'], $event->event_id);

            $errors = \App\Message\Error::get('vehiclegroupsinevent.add');

            if (isset($errors) && count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }
        }

        if (isset($data['vehicles']) && count($data['vehicles']) > 0) {
            $vehiclesInEvent  = new \App\Model\VehiclesInEvent();
            $vehiclesInEvent->addVehiclesInEvent($data['vehicles'], $event->event_id);

            $errors = \App\Message\Error::get('vehiclesinevent.add');

            if (isset($errors) && count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Event has been created.',
            "module" => 'EVENT',
            "request_log_id" => $request_log_id
        ]);
    }

    public function change(Request $request, $eventId) {
        $validator = Validator::make([    
            'event_id' => $eventId
        ],[
            'event_id' => 'nullable|int|min:1|exists:fm_events,event_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        
        $errors = [];

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        if ($request->isMethod('post')) {

            $event = new Event();

            $event = $event->change($data['event'], $eventId);

            if (!is_object($event)) {
                $errors = \App\Message\Error::get('event.change');

                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            if (isset($data['active_on_days'])) {
                
                $eventActiveOnDay = new \App\Model\EventActiveOnDay();
                $eventActiveOnDay->activateDaystForEvent($data['active_on_days'], $eventId);

                $errors = \App\Message\Error::get('eventactiveonday.add');

                if (isset($errors) && count($errors) > 0) {
                    return response()->json([
                        "code" => 500,
                        "errors" => $errors
                    ]);
                }
            }

            if (isset($data['vehicle_groups'])) {
                $vehicleGroupsInEvent  = new \App\Model\VehicleGroupsInEvent();
                $vehicleGroupsInEvent->addVehicleGroupsInEvent($data['vehicle_groups'], $eventId);

                $errors = \App\Message\Error::get('vehiclegroupsinevent.add');

                if (isset($errors) && count($errors) > 0) {
                    return response()->json([
                        "code" => 500,
                        "errors" => $errors
                    ]);
                }
            }

            if (isset($data['vehicles'])) {
                $vehiclesInEvent  = new \App\Model\VehiclesInEvent();
                $vehiclesInEvent->addVehiclesInEvent($data['vehicles'], $eventId);

                $errors = \App\Message\Error::get('vehiclesinevent.add');

                if (isset($errors) && count($errors) > 0) {
                    return response()->json([
                        "code" => 500,
                        "errors" => $errors
                    ]);
                }
            }

            return response()->json([
                "code" => 200,
                "message" => "Event has been updated successfully.",
                "module" => "EVENT",
                "request_log_id" => $request_log_id
            ]);
        }
    }

    public function remove(Request $request, $eventId)
    {
        $validator = Validator::make([    
            'event_id' => $eventId
        ],[
            'event_id' => 'nullable|int|min:1|exists:fm_events,event_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $event = Event::find($eventId);

        if (!is_object($event)) {
            return response()->json([
                "code" => 400,
                "message" => 'Event not found.'
            ]);
        }

        if ($event->status == 1) {
            $event->status = 9;
        }
        else {
            $event->status = 1;
        }

        $event->save();
        $event->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Event has been deleted.',
            "module" => "EVENT",
            "request_log_id" => $request_log_id
        ]);
    }

    public function sendNotification(Request $request) {
        $event_id = $request->input('event_id');

        $event = \App\Model\Template::sendFleetEventNotifications($event_id);

        if (!is_object($event)) {
            return response()->json([
                "code" => 500,
                "message" => "Notification is not sent."
            ]);
        }

        return response()->json([
            "code" => 200,
            "message" => "Notification sent.",
            "event" => $event
        ]);
    }

    public function vehicleEventsLog($vehicle_id) {
        // $vehicle_id = $request->input('vehicle_id');


        $validator = Validator::make([    
            'vehicle_id' => $vehicle_id
        ],[
            'vehicle_id' => 'required|int|exists:vehicles,vehicle_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        try{
            $events = \App\Model\EventLog::with('event:event_id,title,event_type_id','event.eventType:event_type_id,title','vehicles:vehicle_id,vehicle_code,vehicle_plate_number')->where('vehicle_id',$vehicle_id)->withTrashed()->get();
        }
        catch(Exception $ex) {
            array_push($errors, [$ex->getMessage()]);
        }

    //    return $events;

        return response()->json([
            "code" => 200,
            "Data" => [
                "events" => $events
            ],
            "message" => "Data fetched Successfully"
        ]);
    }


    public function eventsOnVehicles($event_id){

        $validator = Validator::make([    
            'event_id' => $event_id
        ],[
            'event_id' => 'required|int|exists:fm_events,event_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        try{
            $vehicles = \App\Model\EventLog::with('event:event_id,title,event_type_id',
            'event.eventType:event_type_id,title','vehicle:vehicle_id,vehicle_code,vehicle_plate_number')
            ->where('event_id', $event_id)->withTrashed()->paginate(15);
        }
        catch(Exception $ex) {
            array_push($errors, [$ex->getMessage()]);
        }

        return response()->json([
            "code" => 200,
            "data" => [
                "events" => $vehicles
            ],
            "message" => "Data fetched Successfully"
        ]);

    }
}