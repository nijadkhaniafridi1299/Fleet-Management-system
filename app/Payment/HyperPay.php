<?php
namespace App\Payment;
use App\Payment;
use App\Model\Option;
use Illuminate\Http\Request;
use App\Model\CustomerPaymentReg;
use App\Model\Order;
use App\Model\Customer;
use App\Cart;

class HyperPay extends Payment
{

  protected $access_token_credit_card;
  protected $entity_id_credit_card;
  protected $access_token_apple_pay;
  protected $entity_id_apple_pay;
  protected $access_token_mada;
  protected $entity_id_mada;
  protected $access_token_stc;
  protected $entity_id_stc;
  protected $access_token;
  protected $entity_id;
  protected $url;

  function __construct()
  {

  }

  function adminForm()
  {
  }

  function adminSettingSave()
  {
  }

  function form()
  {
  }

  function process()
  {
  }

  function saveResponse($detail, $customer_id, $payment_key, $mobile = 0)
  {

    if (isset($detail['cardOnFile']) && isset($detail['tokenId']))
    {
      $payment_type = Option::select('option_meta')->where('option_key', $payment_key)->first();
      $payment_type = json_decode($payment_type->option_meta);
      $payment_type = $payment_type->payment_method_id;
      $regData = [
        'customer_id' => $customer_id,
        'payment_type' => $payment_type,
        'reg_id' => $detail['tokenId'],
        'card_no' => $detail['transactions'][0]['paymentMethod']['maskedCardNumber'],
        'brand' => $detail['transactions'][0]['paymentMethod']['brand'],
        'status' =>1
      ];
      CustomerPaymentReg::firstOrCreate($regData);
    }
    if ($mobile == 1)
    {
      try
      {
        $payment_data = [
          "customer_id" => $customer_id,
          "option_key" => $payment_key,
          "transaction_key" => (isset($detail['transactions'][0]['rrn']) && $detail['transactions'][0]['rrn'] != '') ? $detail['transactions'][0]['rrn'] : $detail['MerchantID'],
          "merchant_id" => $detail['MerchantID'],
          "amount" => isset($detail['Amount']) ? $detail['Amount'] : 0,
          "status" => isset($detail['StatusCode']) ? $detail['StatusCode'] : 0,
          "message" => isset($detail['StatusDescription']) ? $detail['StatusDescription'] : 0,
          "payment_date" => date('Y-m-d H:i:s')
        ];
        $payment = \App\Model\Payment::create($payment_data);
        $result = ["id"=>$detail['refundId']];
        $result = json_encode($result);
        $payment_detail = ["payment_id" => $payment->payment_id, "payment_detail" => $result];
        \App\Model\PaymentDetail::create($payment_detail);
        return true;
      }
      catch(\Exception $e)
      {
        return false;
      }

    }

    $rrn = $detail['merchantId'];
    foreach ($detail['transactions'] as $key => $value) {
      if($value['type'] == 'Pay'){
        $rrn = $value['rrn'];
      }
    }

    $payment_data = [
      "customer_id" => $customer_id,
      "option_key" => $payment_key,
      "transaction_key" => $rrn,
      "merchant_id" => $detail['merchantId'],
      "amount" => isset($detail['amount']) ? $detail['amount'] : 0,
      "status" => isset($detail['status']) ? $detail['status'] : 0,
      "message" => isset($detail['detailedStatus']) ? $detail['detailedStatus'] : 0,
      "payment_date" => date('Y-m-d H:i:s')

    ];
    $payment = \App\Model\Payment::create($payment_data);
    $result = json_encode($detail);
    $payment_detail = ["payment_id" => $payment->payment_id, "payment_detail" => $result, ];
    try
    {
      $paymentDetail = \App\Model\PaymentDetail::create($payment_detail);
    }
    catch(\Exception $ex)
    {
      //echo $ex->getMessage();
      //echo '<pre>'.print_r($payment_detail, true).'</pre>'; exit;
    }

    try {
      if(isset($detail['paymentMethod']['cardholderName'])){
        Customer::find($customer_id)->update(['name'=>$detail['paymentMethod']['cardholderName']]);
      }
    } catch (\Exception $e) {

    }


  }

  function geideaSaveCard($total,$payment_type,$reg_id)
  {
    if(env('APP_ENV') == 'development'){
      $username = '12a9f89a-7712-43ab-b226-b06cfa8d82f8'; // staging
      $password = '42efc579-cf91-473f-9d79-56512c0ef6d8'; // staging
    }else{
      $username = '5af964a4-5c89-43fd-8487-6d247aeada2e'; // production
      $password = '680dfdaf-d664-4d28-b9a2-d8d6212753b5'; // production
    }
    $endpoint = 'https://api.merchant.geidea.net/pgw/api/v1/direct/pay/token';

    $credentials = base64_encode("$username:$password");

    $curl = curl_init();

    $amount = round($total, 2);
    $amount = number_format($amount, 2);
    $amount = str_replace(",", "", $amount);

    $request = [
      'amount'=>$amount,
      'currency'=>'SAR',
      'tokenId'=>$reg_id
  ];

    curl_setopt_array($curl, array(
      CURLOPT_URL => $endpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>json_encode($request),
      CURLOPT_HTTPHEADER => array(
        "Authorization: Basic {$credentials}",
        'Content-Type: application/json'
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response,true);

  }

  function refundPayment($order_id)
  {
    $order = Order::with('payment_method_info:option_key,option_meta->payment_method_id as payment_method_id', 'payment:payment_id,transaction_key,amount,merchant_id', 'payment.detail:payment_id,payment_detail->orderId as ref_payment_id')->find($order_id);
    $response = [];
    if (!$order)
    {
      $response['status'] = false;
      $response['message'] = "no_order_found:{$order_id}";
      return $response;
    }

    if(env('APP_ENV') == 'development'){
      $username = '12a9f89a-7712-43ab-b226-b06cfa8d82f8'; // staging
      $password = '42efc579-cf91-473f-9d79-56512c0ef6d8'; // staging
    }else{
      $username = '5af964a4-5c89-43fd-8487-6d247aeada2e'; // production
      $password = '680dfdaf-d664-4d28-b9a2-d8d6212753b5'; // production
    }

    $endpoint = 'https://api.merchant.geidea.net/pgw/api/v1/direct/refund';

    $credentials = base64_encode("$username:$password");

    $curl = curl_init();

    $request = [
      'orderId'=>$order->payment->detail->ref_payment_id
  ];

    curl_setopt_array($curl, array(
      CURLOPT_URL => $endpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>json_encode($request),
      CURLOPT_HTTPHEADER => array(
        "Authorization: Basic {$credentials}",
        'Content-Type: application/json'
      ),
    ));

    $responseData = curl_exec($curl);

    if (curl_errno($curl))
    {
      $response['status'] = false;
      $response['message'] = json_encode(curl_error($curl));
    }
    curl_close($curl);

    $param = [
      "row_id" => $order->order_id,
      "type" => 21, //for payment refund
      "header" => '',
      "request_body" => $order->payment->detail->ref_payment_id,
      "response" => $responseData,
      "action" => 'true'
    ];

    $responseData = json_decode($responseData);

    if (isset($responseData->order->status) && $responseData->order->status == "Success" && isset($responseData->order->detailedStatus) && $responseData->order->detailedStatus == "Refunded")
    {
      $response['status'] = true;
      $response['message'] = $responseData->detailedResponseMessage;
      $amount = $responseData->order->amount;
    }
    else
    {
      $response['status'] = false;
      $response['message'] = $responseData->detailedResponseMessage;
      $amount = 0;
    }
    $payment_data = [
      "customer_id" => $order->customer_id,
      "option_key" => $order->payment_method_info->option_key,
      "transaction_key" => $order->payment_id,
      "merchant_id" => isset($responseData->order->merchantId)?$responseData->order->merchantId:Null,
      "amount" => isset($amount) ? $amount : 0,
      "status" => isset($responseData->responseCode) ? $responseData->responseCode : 0,
      "message" => isset($responseData->detailedResponseMessage) ? $responseData->detailedResponseMessage : 0,
      "payment_date" => date('Y-m-d H:i:s'),
      "type" => 'refund'
    ];

    $payment = \App\Model\Payment::create($payment_data);
    $result = json_encode($responseData);
    $payment_detail = ["payment_id" => $payment->payment_id, "payment_detail" => $result];
    $paymentDetail = \App\Model\PaymentDetail::create($payment_detail);
    return $response;
  }
}
