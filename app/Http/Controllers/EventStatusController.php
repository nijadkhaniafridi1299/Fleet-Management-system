<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\EventStatus;
use Validator;

class EventStatusController extends Controller

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
    *   path="/event-statuses",
    *   summary="Return the list of Event Statuses",
    *   tags={"Event Statuses"},
    *    @OA\Response(
    *      response=200,
    *      description="List of Event Statuses",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of Event Statuses",
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
        $event_statuses = EventStatus::orderBy('created_at','DESC');
        if(isset($data['title']) && $data['title'] != ""){
            $event_statuses->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $event_statuses->where('status', $data['status']);
        }
        $event_statuses = $event_statuses->paginate($data['perPage']);
        return ["data" => $event_statuses];
    }

    public function show($eventStatusId) {
        $validator = Validator::make([    
            'event_status_id' => $eventStatusId
        ],[
            'event_status_id' => 'int|min:1|exists:fm_event_statuses,event_status_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $event_status = EventStatus::find($eventStatusId);

        return response()->json([
            "event_status" => $event_status
        ]);
    }

    /**
    * @OA\Post(
    *   path="/event-status/add",
    *   summary="Add new event status",
    *   operationId="create",
    *   tags={"event status"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Event Status has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $event_status = new EventStatus();

        //print_r($data); exit;
        $event_status = $event_status->add($data);

        $errors = \App\Message\Error::get('eventstatus.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Event Status has been created.'
        ]);
    }

    public function change(Request $request, $eventStatusId) {
        $validator = Validator::make([    
            'event_status_id' => $eventStatusId
        ],[
            'event_status_id' => 'int|min:1|exists:fm_event_statuses,event_status_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();

            $event_status = new EventStatus();
            $event_status = $event_status->change($data, $eventStatusId);

            if (!is_object($event_status)) {
                $errors = \App\Message\Error::get('eventstatus.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Event Status has been updated successfully."
            ]);
        }
    }

    public function remove($eventStatusId) {
        $validator = Validator::make([    
            'event_status_id' => $eventStatusId
        ],[
            'event_status_id' => 'int|min:1|exists:fm_event_statuses,event_status_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $event_status = EventStatus::find($eventStatusId);

         if ($event_status->status == 1) {
            $event_status->status = 9;
        }
        else {
            $event_status->status = 1;
        }

        $event_status->save();
        $event_status->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Event Status has been deleted.'
        ]);
    }
}
