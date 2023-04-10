<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Device;
use App\Model\Vehicle;

class DeviceController extends Controller

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
    *   path="/devices",
    *   summary="Return the list of devices",
    *   tags={"devices"},
    *    @OA\Response(
    *      response=200,
    *      description="List of devices",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of devices",
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
        $devices = Device::with('vehicles:device_id,vehicle_id,vehicle_plate_number')->orderBy('created_at','DESC');
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;
        
        if(isset($data['imei']) && $data['imei'] != ""){
            $devices->whereRaw('LOWER(`imei`) LIKE ? ',['%'.trim(strtolower($data['imei'])).'%']);
        }
        if(isset($data['sim_card_number']) && $data['sim_card_number'] != ""){
            $devices->whereRaw('LOWER(`sim_card_number`) LIKE ? ',['%'.trim(strtolower($data['sim_card_number'])).'%']);
        }
        if(isset($data['device_serial']) && $data['device_serial'] != ""){
            $devices->whereRaw('LOWER(`device_serial`) LIKE ? ',['%'.trim(strtolower($data['device_serial'])).'%']);
        }
        if(isset($data['connection_state']) && $data['connection_state'] != ""){
            $devices->where('connection_state', $data['connection_state']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $devices->where('status', $data['status']);
        }
        if(isset($data['vehicle_plate_number']) && $data['vehicle_plate_number'] != ""){
            $vehicle_plate_number = $data['vehicle_plate_number'];
            $devices->whereHas('vehicles', function($query) use($vehicle_plate_number){
                $query->whereRaw('LOWER(`vehicle_plate_number`) LIKE ? ',['%'.trim(strtolower($vehicle_plate_number)).'%']);
            });
        }
        
        $devices = $devices->paginate($data['perPage']);

        $device_protocols = \App\Model\DeviceProtocol::orderBy('created_at','DESC')->get();

        $vehicles = Vehicle::where('status','!=',9)->get(['vehicle_id','vehicle_plate_number'])->toArray();

        return response()->json([
            "data" => $devices,
            "device_protocols" => $device_protocols,
            "vehicles" => $vehicles
        ]);
    }

    public function show($deviceId) {
        $device = Device::find($deviceId);

        return response()->json([
            "device" => $device
        ]);
    }

    /**
    * @OA\Post(
    *   path="/device/add",
    *   summary="Add new device",
    *   operationId="create",
    *   tags={"devices"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Device is inserted in database",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $device = new Device();

        //print_r($data); exit;
        $device = $device->add($data);

        $errors = \App\Message\Error::get('device.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Device has been created.'
        ]);
    }

    public function change(Request $request, $deviceId) {
        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();

            $device = new Device();

            $device = $device->change($data, $deviceId);

            if (!is_object($device)) {
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
                "message" => "Device has been updated successfully."
            ]);
        }
    }

    public function remove($deviceId)
    {
        $device = Device::find($deviceId);

        if ($device->status == 1) {
            $device->status = 9;
        }
        else {
            $device->status = 1;
        }

        $device->save();
        $device->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Device has been deleted.'
        ]);
    }
}
