<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\SensorType;
use Validator;

class SensorTypeController extends Controller

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
    *   path="/sensor-types",
    *   summary="Return the list of sensor types",
    *   tags={"sensor types"},
    *    @OA\Response(
    *      response=200,
    *      description="List of sensor types",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of sensor types",
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
        $sensor_types = SensorType::orderBy('created_at','DESC');
        if(isset($data['title']) && $data['title'] != ""){
            $sensor_types->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $sensor_types->where('status', $data['status']);
        }
        $sensor_types = $sensor_types->paginate($data['perPage']);
        return ["data" => $sensor_types];
    }

    public function show($sensorTypeId) {
        $sensor_type = SensorType::find($sensorTypeId);

        return response()->json([
            "sensor_type" => $sensor_type
        ]);
    }

    /**
    * @OA\Post(
    *   path="/sensor-type/add",
    *   summary="Add new sensor type",
    *   operationId="create",
    *   tags={"sensor types"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Sensor Type has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $sensor_type = new SensorType();

        //print_r($data); exit;
        $sensor_type = $sensor_type->add($data);

        $errors = \App\Message\Error::get('sensortype.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Sensor Type has been created.'
        ]);
    }

    public function change(Request $request, $sensorTypeId) {
        $validator = Validator::make([    
            'sensor_type_id' => $sensorTypeId
        ],[
            'sensor_type_id' => 'int|min:1|exists:fm_sensor_types,sensor_type_id'
        ]);
 
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        $errors = [];

        if ($request->isMethod('post')) {
            $data = json_decode($request->getContent(), true);
            $sensor_type = new SensorType();

            $sensor_type = $sensor_type->change($data, $sensorTypeId);

            if (!is_object($sensor_type)) {
                $errors = \App\Message\Error::get('sensortype.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Sensor Type has been updated successfully."
            ]);
        }
    }

    public function remove($sensorTypeId)
    {
        $sensor_type = SensorType::find($sensorTypeId);

        $sensor_type->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Sensor Type has been deleted.'
        ]);
    }
}
