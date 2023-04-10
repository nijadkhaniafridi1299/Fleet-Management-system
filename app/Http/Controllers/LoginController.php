<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\Login;
use Validator;
class LoginController extends Controller

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
public function index(Request $request)
{


    
    
    $rules = [
        'email' => 'required|email|max:255',
        'password' => 'required' ,
        'company_id' => 'nullable|string'


     ];

    $validator = Validator::make($request->all(), $rules);
 
    if ($validator-> fails()){


        return responseValidationError('Fields Validation Failed.', $validator->errors());
        

}



}
}
