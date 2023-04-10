<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Service;
use Validator;

class ServiceController extends Controller

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
    *   path="/services",
    *   summary="Return the list of services",
    *   tags={"services"},
    *    @OA\Response(
    *      response=200,
    *      description="List of services",
    *      @OA\JsonContent(
    *        @OA\Property(
    *          property="data",
    *          description="List of services",
    *          @OA\Schema(
    *            type="array")
    *          )
    *        )
    *      )
    *    )
    * )
    */

    public function index() {
        $services = Service::orderBy('created_at','DESC')->get();
        return ["data" => $services];
    }

    public function show($serviceId) {
        $service = Service::find($serviceId);

        return response()->json([
            "service" => $service
        ]);
    }

    /**
    * @OA\Post(
    *   path="/service/add",
    *   summary="Add new service",
    *   operationId="create",
    *   tags={"service"},
    *   @OA\RequestBody(
    *       required=true,
    *       description="Post object",
    *       @OA\JsonContent(ref="#/components/schemas/PostRequest")
    *    ),
    *   @OA\Response(
    *      response=201,
    *      description="New Service has been created.",
    *    )
    * )
    */

    public function create(Request $request) {
        $errors = [];
        $data = $request->all();

        $service_types = [];
        if (isset($data['service_types'])) {
            $service_types = $data['service_types'];
        }

        unset($data['service_types']);
        if (isset($service_types) && count($service_types) > 0) {
            $service = new Service();
            $service = $service->add($data);
            
            $errors = \App\Message\Error::get('service.add');

            if (isset($errors) && count($errors) > 0) {
                return response()->json([
                    "code" => 400,
                    "errors" => $errors
                ]);
            }

            foreach($service_types as &$service_type) {
                if (!isset($service_type['title']) || $service_type['title'] == '') {
                    return response()->json([
                        "code" => 400,
                        "errors" => ['Title is required for service type']
                    ]);
                }

                $service_type['service_id'] = $service->service_id;
                $service_type['created_at'] = date('Y-m-d H:i:s');
            }

            $servicetype = new \App\Model\ServiceType();
            $servicetype->bulk_insert($service_types);

            $errors = \App\Message\Error::get('servicetype.add');
            if (isset($errors) && count($errors) > 0) {
                return response()->json([
                    "code" => 400,
                    "errors" => $errors
                ]);
            }

             return response()->json([
                "code" => 201,
                "id" =>$service->service_id,
                "message" => 'New Service has been created.'
            ]);
        } else {
            return response()->json([
                "code" => 400,
                "id" => $service->service_id,
                "errors" => ["Services are not defined"]
            ]);
        } 
    }

    public function change(Request $request, $serviceId) {
        $errors = [];

        if ($request->isMethod('post')) {
            $data = $request->all();
            $service_types = [];
            if (isset($data['service_types'])) {
                $service_types = $data['service_types'];
            }

            unset($data['service_types']);

            if (isset($service_types) && count($service_types) > 0) {

                $service = new Service();
                $service = $service->change($data, $serviceId);

                if (!is_object($service)) {
                    $errors = \App\Message\Error::get('service.change');
                }

                if (count($errors) > 0) {
                    return response()->json([
                        "code" => 400,
                        "errors" => $errors
                    ]);
                }

                foreach($service_types as $service_type) {
                    if (!isset($service_type['title']) || $service_type['title'] == '') {
                        return response()->json([
                            "code" => 400,
                            "errors" => ['Title is required for service type']
                        ]);
                    }

                    $service_type['updated_at'] = date('Y-m-d H:i:s');
                    $service_type['service_id'] = $serviceId;

                    $service_type_id = \App\Model\ServiceType::where('service_id', $serviceId)->where('title', $service_type['title'])->value('service_type_id');

                    $servicetype = new \App\Model\ServiceType();
                    if (isset($service_type_id) && $service_type_id > 0) {
                        $servicetype = $servicetype->change($service_type, $service_type_id);
                        $errors = \App\Message\Error::get('servicetype.change');
                    } else {
                        $servicetype = $servicetype->add($service_type);
                        $errors = \App\Message\Error::get('servicetype.add');
                    }

                    if (isset($errors) && count($errors) > 0) {
                        return response()->json([
                            "code" => 400,
                            "errors" => $errors
                        ]);
                    }
                }
            }

            return response()->json([
                "code" => 200,
                "message" => "Service has been updated successfully."
            ]);
        }
    }

    public function remove($serviceId)
    {
        $validator = Validator::make([    
            'service_id' => $serviceId
        ],[
            'service_id' => 'int|min:1|exists:fm_services,service_id,deleted_at,NULL'
        ]);
 
        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
        $service = Service::find($serviceId);

         if ($service->status == 1) {
            $service->status = 9;
        }
        else {
            $service->status = 1;
        }

        $service->save();
        $service->delete();

        return response()->json([
            "code" => 200,
            "message" => 'Service has been deleted.'
        ]);
    }
}
