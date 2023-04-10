<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\Customer;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
class CustomerController extends Controller

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

    public function changePassword(Request $request, $customerId) {
        $validator = Validator::make([
                    
            'customer_id' => $customerId
        ],[
            'customer_id' => 'nullable|int|min:1|exists:customers,customer_id',
  
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
     
        $customer = Customer::find($customerId);

        if (!is_object($customer)) {
            return response()->json([
                "code" => 400,
                "message" => "Customer not found."
            ]);
        }

        $errors = [];
        $data =  json_decode($request->getContent(),true);

        //dd($data);

        if ($data == "") {
			return response()->json([
				"code" => 403,
				"data" => "",
				"message" => "Invalid json."
			]);
		}

		if (!isset($data['old_password']) || empty($data['old_password']) || 
            !isset($data['new_password']) || empty($data['new_password'])) {
			return response()->json([
				"code" => 403,
				"data" => "",
				"message" => "Missing Input."
			]);
		}

		$user = auth()->guard('oms')->user();
		if ($user->customer_id != $customerId) {
			return response()->json([
				"code" => 403,
				"data" => "",
				"message" => "Unauthorized Customer."
			]);
        }

        $customer->changePassword($data, $customerId);
        $errors = \App\Message\Error::get('customer.change');
        
        if (isset($errors) && $errors != null ) {
            return response()->json([
                "code" => 400,
                "data" => "",
                "message" => $errors
            ]);
        }

        return response()->json([
            "code" => 200,
            "message" => 'Password has been changed.'
        ]);
    }


    public function create(Request $request) {


        $data = $request->all();


        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'mobile' => 'required|numeric',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required'
                ]);
        
          if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
          }

          $data['channel_id'] = 1;
          $data['source'] = 1;
          $data['agent_id'] = 1;
          $data['staff_id'] = 1;

          $customer = new Customer();
          $customer = $customer->add($data);
          
        $errors = \App\Message\Error::get('customer.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        return response()->json([
            "code" => 200,
            "Data" => [
               
            ],
            "message" => 'Customer created Successfully',
        ]);
	}
    
    public function update(Request $request, $customer_id) 
    {
        $data = $request->all();
        $email = $request->input('email');
        $validator = Validator::make([
                    
            'customer_id' => $customer_id,
            'email' => $email
        ],[
            'customer_id' => 'nullable|int|min:1|exists:customers,customer_id',
            'email' => 'unique:customers,email,' . $customer_id . ',customer_id'
        ]);
 
        if ($validator-> fails()){
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        


        //step1 update entry in `customers` table
        $customer = new Customer();
        $customer = $customer->change($data, $customer_id);

      
        $errors = \App\Message\Error::get('customer.change');

        if (isset($errors) && $errors != null) {
            return response()->json([
                "code" => 500,
                "errors" => $errors
            ]);
        }

     

        return response()->json([
            "code" => 200,
            "Data" => '',
            "message" => "Customer Updated Successfully"
                    ]);

     
    }


    public function remove(Request $request, $customer_id) {
     
        //$data = json_decode($request->getContent(),true);

        $validator = Validator::make([    
            'customer_id' => $customer_id
        ],[
            'customer_id' => 'required|int|exists:customers,customer_id'
        ]);

        if ($validator->fails()){
    
            return responseValidationError('Fields Validation Failed.', $validator->errors());

        }

        //$geofenceid=$data['id'];
    
        $customer = Customer::where('customer_id',$customer_id)->delete();
        
        return response()->json([
            "code" => 200,
            "message" => "Customer Successfully Deleted"
        ]);
    } 


}
