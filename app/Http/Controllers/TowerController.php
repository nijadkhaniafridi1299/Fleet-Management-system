<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\CancelReason as CancelReason;
use App\Model\Order as Order;
use App\Model\User as User;
use Auth;
use Illuminate\Support\Facades\Hash;
use Validator;
class TowerController extends Controller

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

    public function getCancelReasonsAction(){

        $cancelReasons =CancelReason::getCancelReasons();
        return $cancelReasons;
}

public function updateOrderDeliveryAction(Request $request){


    $user=Auth::user();
 
    $data =  json_decode($request->getContent(),true);

    $rules = [


             'order_id' => 'required|int|min:1',
             'delivery_slot_id' => 'required|int|min:1',
             'date' => 'required|date|date_format:Y-m-d',
             'deliverytime' => 'required'



        ];
    
    $validator = Validator::make($data,$rules);
        if ($validator->fails()){

            return responseValidationError('Fields Validation Failed.', $validator->errors());

        }
       
    $deliverySlots = Order::updateDeliveryOrder($data);
    return $deliverySlots;

}


public function cancelOrderAction(Request $request){

    $data =  json_decode($request->getContent(),true);
    $rules = [


             'order_id' => 'required|int|min:1',
             'cancel_reason_id' => 'required|int|min:1'


        ];

    $validator = Validator::make($data,$rules);
        if ($validator->fails()){

            return responseValidationError('Fields Validation Failed.', $validator->errors());

        }
    $cancelOrder = Order::cancelOrders($data);
    return $cancelOrder;

}

public function updatePassword(Request $request) {
    $user=Auth::user();
 
    if($user){
        $data = $request->getContent();
        $data = json_decode($data, true);

        $old_pwd_hashed = Hash::make($data['old_password']);
        if(!Hash::check($data['old_password'], $user->password)){
            $response = [
                "code" => 406,
                "message" => 'Incorrect Old Password!'
            ];
            return response()->json($response);
        }
        if($data['old_password'] == $data['password']){
            $response = [
                "code" => 406,
                "message" => 'New Password Matches the old Password. Please choose a different password!'
            ];
            return response()->json($response);
        }
        if($data['confirm_password'] != $data['password']){
            $response = [
                "code" => 406,
                "message" => 'Password and Confirm password do not match. Please try again!'
            ];
            return response()->json($response);
        }
        $paramsUser = [
            'password' => Hash::make($data['password']),
            'plain_password' => $data['password']
        ];
        try {
            $usr = User::where('user_id', $user->user_id)->update($paramsUser);
        } catch (\Exception $e) {
            $response = [
                "code" => 409,
                "message" => 'Some Error Occured. Please try again!',
                'error' => $e
            ];
            return response()->json($response);
        } catch (QueryException $e) {
            $response = [
                "code" => 409,
                "message" => 'Some Error Occured. Please try again!',
                'error' => $e
            ];
            return response()->json($response);
        }
        $response = [
            "code" => 200,
            "message" => 'Password Changed Successfully!'
        ];
        return response()->json($response);
    }
    $response = [
        'code' => 401,
        'message' => 'Unauthorized',
    ];
    return response()->json($response);
}

}
