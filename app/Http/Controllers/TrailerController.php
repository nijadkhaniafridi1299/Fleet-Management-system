<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Trailer;
use Validator;
use DB;

class TrailerController extends Controller

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
    *   path="/trailers",
    *   summary="Return the list of trailers",
    *   tags={"trailers"},
    *    @OA\Response(
    *      response=200,
    *      description="List of trailers",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of trailers",
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
        $trailers = Trailer::orderBy('created_at','DESC');
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        if(isset($data['title']) && $data['title'] != ""){
            $trailers->whereRaw('LOWER(`title`) LIKE ? ',['%'.trim(strtolower($data['title'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $trailers->where('status', $data['status']);
        }
        $trailers = $trailers->paginate($data['perPage']);
        return ["data" => $trailers];
    }

    public function show($trailerId) {
        $validator = Validator::make([    
            'trailer_id' => $trailerId
        ],[
            'trailer_id' => 'int|min:1|exists:fm_trailers,trailer_id'
        ]);

        $trailer = Trailer::find($trailerId);

        return response()->json([
            "trailer" => $trailer
        ]);
    }

    /**
    * @OA\Post(
    *   path="/trailer/add",
    *   summary="Add new trailer",
    *   operationId="create",
    *   tags={"trailer"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Trailer has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $data = $request->all();
        $request_log_id = $data['request_log_id'];
      
        unset($data['request_log_id']);

        $errors = [];
        $trailer = new Trailer();
        $trailer = $trailer->add($data);

        $errors = \App\Message\Error::get('trailer.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 201,
            "id" => $trailer->trailer_id,
            "message" => 'New Trailer has been created.',
            "module" => 'TRAILER',
            "request_log_id" => $request_log_id
        ]);
    }

    public function change(Request $request, $trailerId) {
        $validator = Validator::make([    
            'trailer_id' => $trailerId
        ],[
            'trailer_id' => 'int|min:1|exists:fm_trailers,trailer_id'
        ]);

        $data = $request->all();
        $request_log_id = $data['request_log_id'];
      
        unset($data['request_log_id']);

        $errors = [];

        if ($request->isMethod('post')) {

            $trailer = new Trailer();
            $trailer = $trailer->change($data, $trailerId);

            if (!is_object($trailer)) {
                $errors = \App\Message\Error::get('trailer.change');
            }

            if (count($errors) > 0) {
                return response()->json([
                    "code" => 500,
                    "errors" => $errors
                ]);
            }

            return response()->json([
                "code" => 200,
                "message" => "Trailer has been updated successfully.",
                "module" => 'TRAILER',
                "request_log_id" => $request_log_id
            ]);
        }
    }

    public function remove(Request $request, $trailerId)
    {
        $validator = Validator::make([    
            'trailer_id' => $trailerId
        ],[
            'trailer_id' => 'int|min:1|exists:fm_trailers,trailer_id'
        ]);

        $data = $request->json()->all();//$request->all();
        $request_log_id = $data['request_log_id'];

        $trailer = Trailer::find($trailerId);

         if ($trailer->status == 1) {
            $trailer->status = 9;
        }
        else {
            $trailer->status = 1;
        }

        $trailer->save();
        $trailer->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Trailer has been deleted.',
            "module" => 'TRAILER',
            "request_log_id" => $request_log_id
        ]);
    }
}
