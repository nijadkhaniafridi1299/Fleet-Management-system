<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ExampleController extends Controller

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

    //

    /**
    * @OA\Get(

     *  path="/v1/test",

     *  operationId="accountValidate",

     *  summary="validates an account",

     *  @OA\Parameter(name="email",

     *    in="query",

     *    required=true,

     *    @OA\Schema(type="string")

     *  ),

     *  @OA\Response(response="200",

     *    description="Validation Response",

     *  )

     * )

     */

}
