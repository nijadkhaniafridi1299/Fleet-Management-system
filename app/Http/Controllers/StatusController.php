<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Status;
use Validator;

class StatusController extends Controller

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
    *   path="/statuses",
    *   summary="Return the list of statuses",
    *   tags={"statuses"},
    *    @OA\Response(
    *      response=200,
    *      description="List of statuses",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of statuses",
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

        $statuses = Status::orderBy('created_at','DESC');
        $statuses->paginate($data['perPage']);

        if(isset($data['title']) && $data['title'] != ""){
            $statuses->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $statuses->where('status', $data['status']);
        }
        $statuses = $statuses->paginate($data['perPage']);
        return ["data" => $statuses];
    }

    public function show($statusId) {
        $validator = Validator::make([    
            'status_id' => $statusId
        ],[
            'status_id' => 'int|min:1|exists:fm_statuses,status_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $status = Status::find($statusId);

        return response()->json([
            "status" => $status
        ]);
    }

    /**
    * @OA\Post(
    *   path="/status/add",
    *   summary="Add new status",
    *   operationId="create",
    *   tags={"status"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Status has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $status = new Status();

        //print_r($data); exit;
        $status = $status->add($data);

        $errors = \App\Message\Error::get('status.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Status has been created.',
            "module" => "STATUS",
            "request_log_id" => $request_log_id
        ]);
    }

    public function change(Request $request, $statusId) {
        $validator = Validator::make([    
            'status_id' => $statusId
        ],[
            'status_id' => 'int|min:1|exists:fm_statuses,status_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];
        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        if ($request->isMethod('post')) {

            $status = new Status();
            $status = $status->change($data, $statusId);

            if (!is_object($status)) {
                $errors = \App\Message\Error::get('status.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Status has been updated successfully.",
                "module" => "STATUS",
                "request_log_id" => $request_log_id
            ]);
        }
    }

    public function remove(Request $request, $statusId) {
        $validator = Validator::make([    
            'status_id' => $statusId
        ],[
            'status_id' => 'int|min:1|exists:fm_statuses,status_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        $status = Status::find($statusId);

        if (!is_object($status)) {
            return response()->json([
                "code" => 400,
                "message" => "Status not found."
            ]);
        }

        if ($status->status == 1) {
            $status->status = 9;
        } else {
            $status->status = 1;
        }

        $status->save();
        $status->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Status has been deleted.',
            "module" => "STATUS",
            "request_log_id" => $request_log_id
        ]);
    }
}
