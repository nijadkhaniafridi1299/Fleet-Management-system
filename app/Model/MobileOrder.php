<?php

namespace App\Model;
use App\Model\Order as AppOrder;
use App\Model\CancelReason as AppCancelReasons;
use App\Order as GetOrder;
use App\CheckPromocode as AppPromocode;
use Modules\Services\Http\Controllers\Erp\Internal\ErpCustomerController;
use Modules\Services\Http\Controllers\Erp\Internal\ErpAddressController;
use Modules\Services\Http\Controllers\Erp\Internal\ErpOrderController;
use App\Model\Payment;
use App\Message\Error;
use Modules\Services\Authenticate\Mobile;


class MobileOrder extends AppOrder{

  protected $result;
  protected $customerErp;
  protected $addressErp;
  protected $orderErp;

  function __construct(){

  }

  public function syncOrderInErp($order_id){

    $this->customerErp = new ErpCustomerController();
    $this->addressErp = new ErpAddressController();
    $this->orderErp = new ErpOrderController();

    $this->result = ["Customer" => array(), "Address" => array(), "Order" => array()];


    $order = static::with('address','customer')
    ->whereNull('orders.erp_id')
    ->whereIn('orders.order_status_id', [2,5])
    ->where('order_id',$order_id)
    ->get();

    if(count($order) > 0){ //print_r($order->toArray());exit;
      $getOrderData = $order->toArray();
      /* Add customer in ERP if not exist - Start */
      if($getOrderData[0]['customer']['erp_id'] == NULL || $getOrderData[0]['customer']['erp_id'] == ""){

        $this->result['Customer'][0] = $this->customerErp->AddCustomerInErp($getOrderData[0]['address']['address_id']);
        if(isset($this->result['Customer'][0]['address_erp_id']) && !empty($this->result['Customer'][0]['address_erp_id'])){
          $getOrderData[0]['address']['erp_id'] = $this->result['Customer'][0]['address_erp_id'];
        }
      }
      /* Add customer in ERP if not exist  - End */

      /* Add Address in ERP if not exist - Start */
      if($getOrderData[0]['address']['erp_id'] == NULL || $getOrderData[0]['address']['erp_id'] == ""){

        $this->result['Address'][0] = $this->addressErp->AddAddressInErp($getOrderData[0]['address']['address_id']);
      }
      /* Add Address in ERP if not exist  - End */

      /* Add Order in ERP if not exist - Start */
      if($getOrderData[0]['erp_id'] == NULL || $getOrderData[0]['erp_id'] == ""){

        $this->result['Order'][0] = $this->orderErp->AddOrderInErp($getOrderData[0]['order_id']);
      }
      /* Add Order in ERP if not exist  - End */
    }else{
      return false;
    }
  }

  public function getOrderIdByOrderCode($order_code){
    $order = static::select("order_id")->where("order_number",$order_code)->get();
    if(is_object($order)){
      $order =  $order->toArray();
      return $order[0]['order_id'];
    }
    else{
      return false;
    }
  }

  public function getOrderCodeByOrderId($order_id){
    $order = static::onWriteConnection()->select("order_number")->where("order_id",$order_id)->get();
    if(is_object($order)){
      $order =  $order->toArray();
      return $order[0]['order_number'];
    }
    else{
      return false;
    }
  }


  public function fetchLastOrderAddress($client_id)
  {
    return $this->getLastOrderAddress($client_id);
  }

  public function getOrderDetail($order_id,$client_id)
  {
    $GetOrder = new GetOrder();
    return $GetOrder->getDetails($order_id,$client_id);
  }

  public function getCustomerOrders($data)
  {

    $orders = static::with('order_status','payment_method_info')->where("customer_id", $data['user_id'])->orderBy('order_id', 'DESC');

    if(isset($data['request_number']) && $data['request_number'] != ""){
      $orders->where('order_number',$data['request_number']);
    }
   
    if(isset($data['pickup_site']) && $data['pickup_site'] != ""){
        $orders->where('pickup_address_id',">=",$data['pickup_site']);
    }

    if(isset($data['required_start_date']) && $data['required_start_date'] != ""){
        $orders->where('required_start_date',">=",$data['required_start_date']);
    }

    if(isset($data['estimated_end_date']) && $data['estimated_end_date'] != ""){
        $orders->where('estimated_end_date',">=",$data['estimated_end_date']);
    }

    if(isset($data['from']) && $data['from'] != ""){
      $orders->where('created_at','>=',$data['from']);
    }
    if(isset($data['to']) && $data['to'] != ""){
      $orders->where('created_at','<=',$data['to']);
    }

    $orders = $orders->get();

    return $orders;
  }

  public function addOrderToFav($data)
  {
    $isValidOrder = static::where('order_id', $data['order_id'])->get()->toArray();
    //print_r($isValidOrder);exit;
    if($isValidOrder[0]['customer_id'] == $data['client_id'])
    {
      $GetOrder = new GetOrder();
      return $GetOrder->makeFavourite($data['order_id']);
    }
    else
    {
      return false;
    }

  }

  public function undoFavOrder($data)
  {
    $isValidOrder = static::where('order_id', $data['order_id'])->get()->toArray();
    //print_r($isValidOrder);exit;
    if(isset($isValidOrder[0]['customer_id'])){
      if($isValidOrder[0]['customer_id'] == $data['client_id'])
      {
        $GetOrder = new GetOrder();
        return $GetOrder->makeUnfavourite($data['order_id']);
      }
      else
      {
        return false;
      }
    }

  }

  public function makeOrderCancel($data)
  { //print_r($data);exit;
    $deviceType = new Mobile();
    $device = $deviceType->getDeviceType();
    $isValidOrder = static::where('order_id', $data['order_id'])->get()->toArray();
    //print_r($isValidOrder);exit;
    if(isset($isValidOrder[0]['customer_id'])){
      if($isValidOrder[0]['customer_id'] == $data['client_id'])
      {
        $GetOrder = new GetOrder();
        return $GetOrder->cancel($data['order_id'],$data['cancel_reason_id'], $data['client_id'], $device,$data['refund_to']);
      }
      else
      {
        return false;
      }
    }

  }

  public function getCancelOrderReasons()
  {
    return AppCancelReasons::where('status',1)
    ->whereNotNull('reason_code')
    ->where('mobile_visible',1)
    ->get()->toArray();
  }

  public function checkPromoDiscount($data,$stc=''){
    $promo = new AppPromocode();
    return $promo->isValid($data,$stc);

  }

  public function saveOrderRating($data){
    $order = AppOrder::where("order_id", $data['order_id'])->first();
    if(is_object($order)){

      $getOrder = $order->toArray();

      if(($getOrder['customer_id'] == $data['client_id']) && ($getOrder['order_id'] == $data['order_id']))
      {
        if(isset($data['product_quality']) && !empty($data['product_quality'])){
          $order->prod_quality_rating = $data['product_quality'] ;
        }
        // $order->delivery_time_rating = $data['delivery_time'];
        $order->customer_serv_rating = $data['customer_service'];
        $order->rating_created_at = date('Y/m/d H:i:s') ;
        $order->enable_feedback = 0 ;
        $order->customer_comments = $data['customer_comments'] ;
        try{
          return $order->save();
        }
        catch(\Exception $ex){
          return false;
        }
      }
      else{
        return false;
      }


    }
    else{
      return false;
    }
  }

  static function GetLast3HourOrder($clientId){

    $order =  Order::select('order_id','shipping_address_id')
    ->where('customer_id', $clientId)
    ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR)')
    ->with('items')->get();

    if(count($order) > 0){
      return $order->toArray();
    }
    else{
      return false;
    }

  }



  static function GetDuplicateOrders($customer_id)
  {
    return Order::select('order_number')
    ->where('customer_id', $customer_id)
    ->whereIn('order_status_id', array(2,5))
    ->get();
  }

  public function savePaymentResponse($response,$orderId,$grandTotal,$paymentType){

    if(isset($response['id']) && isset($response['message']) && isset($response['status'])){
      $payment = Payment::insert(['order_id' => $orderId,
      'payment_method' => $paymentType,
      'option_key' => $paymentType,
      'transaction_key' => $response['id'],
      'amount' => $grandTotal,
      'status' => $response['status'] == 'paid' ? 1 : 0,
      'message' => $response['message'],
      'payment_date' =>  date('Y-m-d H:i:s') ]);
      return $payment;
    }


  }

}
