<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Parameter;

class ParameterController extends Controller

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
    *   path="/parameters",
    *   summary="Return the list of sensor parameters",
    *   tags={"sensor parameters"},
    *    @OA\Response(
    *      response=200,
    *      description="List of sensor parameters",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of sensor parameters",
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
        $parameters = Parameter::orderBy('created_at','DESC');
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        if(isset($data['title']) && $data['title'] != ""){
            $parameters->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $parameters->where('status', $data['status']);
        }
        $parameters = $parameters->paginate($data['perPage']);

        return ["data" => $parameters];
    }

    public function show($parameterId) {
        $parameter = Parameter::find($parameterId);

        return response()->json([
            "parameter" => $parameter
        ]);
    }

    /**
    * @OA\Post(
    *   path="/parameter/add",
    *   summary="Add new sensor parameter",
    *   operationId="create",
    *   tags={"sensor parameters"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Parameter has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();
        $parameter = new Parameter();

        //print_r($data); exit;
        $parameter = $parameter->add($data);

        $errors = \App\Message\Error::get('parameter.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "message" => 'New Parameter has been created.'
        ]);
    }

    public function change(Request $request, $parameterId) {
        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();

            $parameter = new Parameter();

            $parameter = $parameter->change($data, $parameterId);

            if (!is_object($parameter)) {
                $errors = \App\Message\Error::get('parameter.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Parameter has been updated successfully."
            ]);
        }
    }

    public function remove($parameterId)
    {
        $parameter = Parameter::find($parameterId);

        $parameter->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Parameter has been deleted.'
        ]);
    }
}
