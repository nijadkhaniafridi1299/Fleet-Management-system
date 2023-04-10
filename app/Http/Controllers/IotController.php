<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use  App\Model\Customer;
use  App\Model\Product;
use  App\Model\Cart;
use  App\Model\Category;
use  App\Model\Location;
use  App\Model\DeliverySlot;
use  App\Model\Option;
use  App\Model\Order;
use App\Model\ExtCompContract ;
use App\Model\Promocode ;
use App\Model\SimilarProduct ;
use App\Model\CustomerPaymentReg ;
use App\Model\MobileAddress as Address ;
use App\Model\SaveOrder ;
use App\Model\MobilePayment ;
use App\Model\OrderItem ;
use App\Payment\HyperPay;
use App\Model\IoTButton;
use App\Http\Middleware\SaveMobileRequest as Mobile;
use App\Model\MobileOrder as GetOrder;
use App\Http\Controllers\OrderController;
use DB;
use Validator;
Use \Carbon\Carbon;
use Illuminate\Database\Query\Builder;

class IotController extends Controller
{

    public function action(Request $request) {

        $data =  json_decode($request->getContent(),true);

        $mobile = new Mobile();
        $orderModal = new GetOrder();

          $validator = Validator::make($request->all(), [
            'device_imei' => 'required|integer|exists:iot_buttons,imei'
          ]);
          if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
          }


          // if(!isset($data['click_type'])){
          //   return response()->json([
          //     "code" => 403,
          //     "message" => "Click type is missing"
          //   ]);
          // }
        
          //  $customer = Customer::where('device_id',$data['device_id'])->value('customer_id');
          //  $request->request->add(['user_id'=>$customer]);

          // if($data['click_type'] == 1){
            // $product = Product::where(['product_id' => 3, 'category_id' => 2])->get()->toArray();
            // $cart = ['count' => 1,
            //          'dish_id' => 3,
            //          'product_price' => $product[0]['price'],
            //          'productVariantId' => null,
            //         ];
            //     }
            $date = Carbon::now();
            $tomorrow = Carbon::tomorrow();
           
            $customer_id = IoTButton::where('imei',$data['device_imei'])->get(['customer_id','address_id']);
            $request->request->add(['user_id' => $customer_id[0]['customer_id']]);
            $request->request->add(['iot_request' => 1]); 
            $request->request->add(['category_id' => 1]); 
            $request->request->add(['start_date' => $date]); 
            $request->request->add(['end_date' => $tomorrow]); 
            $request->request->add(['cart'=>[]]); 

            $request->request->add(
              [ 'addressData' => [
                        'ord_address_id' => $customer_id[0]['address_id']
            ]]
          );
            
            $place_order = new OrderController;
            return $place_order->placeOrder($request);
          
 }
  
  }
