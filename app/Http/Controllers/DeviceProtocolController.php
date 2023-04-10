<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\DeviceProtocol;
use Validator;

class DeviceProtocolController extends Controller

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
    *   path="/device-protocols",
    *   summary="Return the list of device_protocolss",
    *   tags={"device_protocols"},
    *    @OA\Response(
    *      response=200,
    *      description="List of device_protocols",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of device_protocols",
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

        $device_protocols = DeviceProtocol::orderBy('created_at','DESC');
        if(isset($data['title']) && $data['title'] != ""){
            $device_protocols->whereRaw('LOWER(`device_protocol_title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
            
        }
        if(isset($data['status']) && $data['status'] != ""){
            $device_protocols->where('status', $data['status']);
        }
        $device_protocols = $device_protocols->paginate($data['perPage']);
        return ["data" => $device_protocols];
    }

    public function show($deviceProtocolId) {
        $validator = Validator::make([
                    
            'device_protocol_id' => $deviceProtocolId
        ],[
            'device_protocol_id' => 'int|min:1|exists:fm_device_protocols,device_protocol_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $device_protocol = DeviceProtocol::find($deviceProtocolId);

        return response()->json([
            "device_protocol" => $device_protocol
        ]);
    }

    /**
    * @OA\Post(
    *   path="/device-protocol/add",
    *   summary="Add new device protocol",
    *   operationId="create",
    *   tags={"device protocols"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New device protocol is inserted in database",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $device_protocol = new DeviceProtocol();

        $device_protocol = $device_protocol->add($data);

        $errors = \App\Message\Error::get('deviceprotocol.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Device Protocol has been created.',
            "id" => $device_protocol->device_protocol_id
        ]);
    }

    public function change(Request $request, $deviceProtocolId) {
        $validator = Validator::make([
                    
            'device_protocol_id' => $deviceProtocolId
        ],[
            'device_protocol_id' => 'int|min:1|exists:fm_device_protocols,device_protocol_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();

            $device_protocol = new DeviceProtocol();

            $device_protocol = $device_protocol->change($data, $deviceProtocolId);

            if (!is_object($device_protocol)) {
                $errors = \App\Message\Error::get('device.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Device Protocol has been updated successfully."
            ]);
        }
    }

    public function remove($deviceProtocolId)
    {
        $validator = Validator::make([
                    
            'device_protocol_id' => $deviceProtocolId
        ],[
            'device_protocol_id' => 'int|min:1|exists:fm_device_protocols,device_protocol_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $device_protocol = DeviceProtocol::find($deviceProtocolId);

        if (!is_object($device_protocol)) {
            return response()->json([
                "code" => 400,
                "message" => "Device Protocol not found."
            ]);
        }

        if ($device_protocol->status == 1) {
            $device_protocol->status = 9;
        }
        else {
            $device_protocol->status = 1;
        }

        $device_protocol->save();
        $device_protocol->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Device Protocol has been deleted.'
        ]);
    }
}
