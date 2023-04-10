<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\DriverBehavior;

class DriverBehaviorController extends Controller

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
    *   path="/driver-behaviors",
    *   summary="Return the list of driver behaviors",
    *   tags={"driver behaviors"},
    *    @OA\Response(
    *      response=200,
    *      description="List of driver behaviors",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of driver behaviors",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */

    public function index() {
        $driver_behaviors = DriverBehavior::orderBy('created_at','DESC')->get();
        return ["data" => $driver_behaviors];
    }

    public function show($driverBehaviorId) {
        $driver_behavior = DriverBehavior::find($driverBehaviorId);

        return response()->json([
            "driver_behavior" => $driver_behavior
        ]);
    }

    /**
    * @OA\Post(
    *   path="/driver-behavior/add",
    *   summary="Add new driver behavior",
    *   operationId="create",
    *   tags={"driver behavior"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New driver behavior is inserted in database",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $driver_behavior = new DriverBehavior();

        //print_r($data); exit;
        $driver_behavior = $driver_behavior->add($data);

        $errors = \App\Message\Error::get('driverbehavior.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "id" => $driver_behavior->driver_behavior_id,
            "message" => 'New Driver Behavior has been created.'
        ]);
    }

    public function change(Request $request, $driverBehaviorId) {
        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();

            $driver_behavior = new DriverBehavior();

            $driver_behavior = $driver_behavior->change($data, $driverBehaviorId);

            if (!is_object($driver_behavior)) {
                $errors = \App\Message\Error::get('driverbehavior.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Driver Behavior has been updated successfully."
            ]);
        }
    }

    public function remove($driverBehaviorId)
    {
        $driver_behavior = DriverBehavior::find($driverBehaviorId);

        if ($driver_behavior->status == 1) {
            $driver_behavior->status = 9;
        }
        else {
            $driver_behavior->status = 1;
        }

        $driver_behavior->save();
        $driver_behavior->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Driver Behavior has been deleted.'
        ]);
    }
}
