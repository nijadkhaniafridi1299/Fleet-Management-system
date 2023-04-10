<?php

namespace App\Model;

use App\Mail\Notification as MailNotification;
use App\Model\Customer;
use App\Model\Order;
use App\Model\OrderStatus;
use App\Model\Template as AppTemplate;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Model\Invoice;
use App\Model\OrderLog;
use Illuminate\Support\Facades\DB;

class NotificationTemplate extends AppTemplate{

  public static function sendOrderStatusNotifications($order_id, $order_status_id = 0) {
    if (!isset($order_id) || $order_id == 0) {
      //get last order
      return [
        "code" => 400,
        "message" => "Customer Order not found, Notification can not be sent."
      ];
    }

    $order = Order::with('items.product')->find($order_id);

    if (!is_object($order)) {
      return [
        "code" => 400,
        "message" => "Customer Order not found, Notification can not be sent."
      ];
    }

    //dump($order);
    $customer_id = $order['customer_id'];

    if (!isset($customer_id) || $customer_id == 0) {
      return [
        "code" => 400,
        "message" => "Customer not found, Notification can not be sent."
      ];
    }

    $customer = Customer::where('customer_id', $customer_id)->first();

    if (!is_object($customer)) {
      return [
        "code" => 400,
        "message" => "Customer not found, Notification can not be sent."
      ];
    }

    //dump($customer);

    $error = "";

    if (!isset($order_status_id) || $order_status_id == 0) {
      $order_status_id = $order->order_status_id;
    }

    if(($order->refund_to == 'bank' || $order->refund_to == 'wallet') && $order->order_status_id == 6){
      $order_status_id = 11;
    }

    $orderStatus =  OrderStatus::with('emailTemplate', 'smsTemplate', 'pushNotificationTemplate','invoiceTemplate','adminEmailTemplate')
    ->where('order_status_id', $order_status_id)
    ->where('status', 1)
    ->first();
    $order_status_id = ($order_status_id == 11)?6:$order_status_id;

    //dump($orderStatus);
    if (!is_object($orderStatus)) {
      return [
        "code" => 400,
        "message" => "Order status is not valid, Notification can not be sent."
      ];
    }

    $preferred_communication_id = 'ar';

    if (isset($customer->preferred_language_id)) {
      $preferred_communication_id = $customer->preferred_language_id;
    }

    $email = '';
    $admin_email = '';
    $subject = '';
    $sms = '';
    $pushNotification = '';
    $order_number = '';

    if (isset($order['order_number'])) {
      $order_number = $order['order_number'];
    }

    if ($preferred_communication_id == 'en') {
      if (isset($orderStatus) && isset($orderStatus['emailTemplate']) &&
      isset($orderStatus['emailTemplate']['english'])) {

        $email = $orderStatus['emailTemplate']['english'];

        if (isset($orderStatus['emailTemplate']['subject'])) {
          $subject = @json_decode($orderStatus['emailTemplate']['subject'])->en;
        }
      }



      if (isset($orderStatus) && isset($orderStatus['adminEmailTemplate']) &&
      isset($orderStatus['adminEmailTemplate']['english'])) {

        $admin_email = $orderStatus['adminEmailTemplate']['english'];

        if (isset($orderStatus['adminEmailTemplate']['subject'])) {
          $admin_subject = @json_decode($orderStatus['adminEmailTemplate']['subject'])->en;
        }
      }

      if (isset($orderStatus) && isset($orderStatus['smsTemplate']) &&
      isset($orderStatus['smsTemplate']['english'])) {
        $sms = $orderStatus['smsTemplate']['english'];
      }

      if (isset($orderStatus) && isset($orderStatus['pushNotificationTemplate']) &&
      isset($orderStatus['pushNotificationTemplate']['english'])) {
        $pushNotification = $orderStatus['pushNotificationTemplate']['english'];

        if (isset($orderStatus['pushNotificationTemplate']['subject'])) {
          $subject = @json_decode($orderStatus['pushNotificationTemplate']['subject'])->en;
        }
      }

      if (isset($orderStatus) && isset($orderStatus['invoiceTemplate'])){
        $invoiceTemplate = true;
      }

    } else {
      if (isset($orderStatus) && isset($orderStatus['emailTemplate']) &&
      isset($orderStatus['emailTemplate']['arabic'])) {
        $email = $orderStatus['emailTemplate']['arabic'];

        if (isset($orderStatus['emailTemplate']['subject'])) {
          $subject = @json_decode($orderStatus['emailTemplate']['subject'])->ar;
        }
      }


      /////////////////codeChanges
      if (isset($orderStatus) && isset($orderStatus['adminEmailTemplate']) &&
      isset($orderStatus['adminEmailTemplate']['arabic'])) {
        $admin_email = $orderStatus['adminEmailTemplate']['arabic'];

        if (isset($orderStatus['adminEmailTemplate']['subject'])) {
          $admin_subject = @json_decode($orderStatus['adminEmailTemplate']['subject'])->ar;
        }
      }
      ///////////////////


      if (isset($orderStatus) && isset($orderStatus['smsTemplate']) &&
      isset($orderStatus['smsTemplate']['arabic'])) {
        $sms = $orderStatus['smsTemplate']['arabic'];
      }

      if (isset($orderStatus) && isset($orderStatus['invoiceTemplate'])){
        $invoiceTemplate = true;
      }

     

      if (isset($orderStatus) && isset($orderStatus['pushNotificationTemplate']) &&
      isset($orderStatus['pushNotificationTemplate']['arabic'])) {
        $pushNotification = $orderStatus['pushNotificationTemplate']['arabic'];

        if (isset($orderStatus['pushNotificationTemplate']['subject'])) {
          $subject = @json_decode($orderStatus['pushNotificationTemplate']['subject'])->ar;
        }
      }
    }

    $email_matches = [];

    /*
    *
    * Generate and save order pdf on disk
    * Save Order Invoice
    */

    if($order['order_status_id'] == 4){

      $company_registration = \App\Model\Option::where('option_key', 'COMPANY_VAT_REGISTRATION')->get()->toArray();
      if(isset($company_registration[0]['option_value'])){
        $company_registration = $company_registration[0]['option_value'];
      }else{
        $company_registration = '';
      }

      $head_office = \App\Model\Option::where('option_key', 'COMPANY_HEAD_OFFICE')->get()->toArray();
      if(isset($head_office[0]['option_value'])){
        $head_office = $head_office[0]['option_value'];
      }else{
        $head_office = '';
      }

      $order_number = $order["order_number"];

      $data = ['customer' => $customer , 'order' => $order, 'company_registration' => $company_registration, 'head_office' => $head_office ];


      $path = public_path('invoices/');
      // Generate Delivery Invoice start
      if($customer['account_type_id'] == 0){

        if($order['tax_invoice'] == null || !file_exists($path.$order['tax_invoice'])){
          $tax_pdf = \PDF::loadView('yaa_layouts.tax_invoice', $data);

          $rand_tax_num = rand();
          $tax_fileName =  'tax_invoice_'. $rand_tax_num . $order["order_number"] . '.' . 'pdf' ;
          $update_order_pickup_invoice = Order::where(['order_number' => $order_number])->update(['tax_invoice' => $tax_fileName]);
          $tax_pdf->save($path . '/' . $tax_fileName);
          $pdf_path = asset('public/invoices/' . $tax_fileName);

        //   if(isset($invoiceTemplate) && $invoiceTemplate == true && $customer['email'] != ''){
        //     $email = $customer['email'];
        //     try{Mail::send([],[], function ($m) use($email,$order_number) {
        //       $m->from('tech@yaafoods.com', 'YaaFoods');
        //       $m->to($email)
        //       ->subject("Order Invoice [".date('Y-m-d')."]")
        //       ->attach(public_path('invoices/') . 'tax_invoice_'. $rand_tax_num . $order_number . '.' . 'pdf' );
        //
        //     });
        //   }
        //   catch (\Exception $e) {
        //   }
        // }
      }else{
        $pdf_path = asset('public/invoices/' . $order['tax_invoice']);
      }
      // Generate Delivery Invoice end
    }else{

      // Generate Corporate Invoice start
      if($order['tax_invoice'] == null || !file_exists($path.$order['tax_invoice'])){

        $invoice = Invoice::where('type','b2b')->first();
        $invoice_no = $invoice['prefix'].$invoice['current_count'].$invoice['postfix'];
        $update_order_invoice_no = Order::where(['order_number' => $order_number])->update(['invoice_no' => $invoice_no]);

        $data['order']['invoice_no'] = $invoice_no;

        $tax_pdf = \PDF::loadView('yaa_layouts.corporate_invoice', $data);

        $rand_tax_num = rand();
        $tax_fileName =  'corporate_invoice_'. $rand_tax_num . $order["order_number"] . '.' . 'pdf' ;

        $update_order_pickup_invoice = Order::where(['order_number' => $order_number])->update(['tax_invoice' => $tax_fileName]);

        if((int)$invoice['current_count'] < 10){
          $invoice->current_count = '0'.($invoice->current_count+1);
          $invoice->save();
        }else{
          $update_current_count = Invoice::where('type','b2b')->update([
            'current_count'=> DB::raw('current_count+1')]);
          }

          $tax_pdf->save($path . '/' . $tax_fileName);

          $pdf_path = asset('public/invoices/' . $tax_fileName);

        //   if(isset($invoiceTemplate) && $invoiceTemplate == true && $customer['email'] != ''){
        //     $email = $customer['email'];
        //     try{Mail::send([],[], function ($m) use($email,$order_number) {
        //       $m->from('tech@yaafoods.com', 'YaaFoods');
        //       $m->to($email)
        //       ->subject("Order Invoice [".date('Y-m-d')."]")
        //       ->attach(public_path('invoices/') . 'corporate_invoice_'. $rand_tax_num . $order_number . '.' . 'pdf' );
        //
        //     });
        //   }
        //   catch (\Exception $e) {
        //   }
        // }
      }else {
        $pdf_path = asset('public/invoices/' . $order['tax_invoice']);
      }
      // Generate Corporate Invoice end
    }

  }

    if (strlen($email) > 0) {
      preg_match_all('/__(.*?)__/', $email, $email_matches);
      if (count($email_matches[1]) > 0 && count($email_matches[1]) > 0) {
        for ($i=0; $i < count($email_matches[1]); $i++) {

          switch($email_matches[1][$i]) {
            case "CUSTOMERNAME":

            $name = '';
            if (isset($customer['name'])) {

              $name = $customer['name'];
            }

            $email = str_replace($email_matches[0][$i], $name, $email);
            break;

            case "ORDERID":

            $email = str_replace($email_matches[0][$i], $order_number, $email);

            break;

            case "ORDERLINK":
            $id = $order_number;//Crypt::encrypt($order_id);
            // $link = route('order.view', ['orderId' => $id]);
            // $tag = '<a href="' . $link . '">View Order</a>';
            // $email = str_replace($email_matches[0][$i], $link, $email);
            // break;

            case "INVOICELINK":
            $link = isset($pdf_path)?$pdf_path:'';
            $tag = '<a href="' . $link . '">View Invoice</a>';
            $email = str_replace($email_matches[0][$i], $link, $email);
            break;
          }
        }
      }
    }

    if (strlen($sms) > 0) {
      preg_match_all('/__(.*?)__/', $sms, $sms_matches);
      //dd($sms_matches);
      if (count($sms_matches[0]) > 0 && count($sms_matches[1]) > 0) {
        for ($i=0; $i < count($sms_matches[1]); $i++) {

          switch($sms_matches[1][$i]) {
            case "CUSTOMERNAME":

            $name = '';
            if (isset($customer['name'])) {

              $name = $customer['name'];
            }
            $sms = str_replace($sms_matches[0][$i], $name, $sms);
            break;

            case "ORDERID":

            $sms = str_replace($sms_matches[0][$i], $order_number, $sms);

            break;

            case "ORDERLINK":
            // $link = route('order.view', ['orderId' => $order_id]);
            // $sms = str_replace($sms_matches[0][$i], $link, $sms);
            // break;
          }
        }
      }
    }

    if (strlen($pushNotification) > 0) {
      preg_match_all('/__(.*?)__/', $pushNotification, $push_notification_matches);
      //dd($sms_matches);
      if (count($push_notification_matches[0]) > 0 && count($push_notification_matches[1]) > 0) {
        for ($i=0; $i < count($push_notification_matches[1]); $i++) {

          switch($push_notification_matches[1][$i]) {
            case "CUSTOMERNAME":

            $name = '';
            if (isset($customer['name'])) {

              $name = $customer['name'];
            }
            $pushNotification = str_replace($push_notification_matches[0][$i], $name, $pushNotification);
            break;

            case "ORDERID":

            $pushNotification = str_replace($push_notification_matches[0][$i], $order_number, $pushNotification);
            break;

            case "ORDERLINK":
            // $link = route('order.view', ['orderId' => $order_id]);
            // $pushNotification = str_replace($push_notification_matches[0][$i], $link, $pushNotification);
            // break;
          }
        }
      }

    }


    $admin_to_emails = \App\Model\Option::getValueByKey('ORDER_ADMIN_EMAIL');
    $admin_to_emails = json_decode($admin_to_emails,true);
    $is_admin_email = false;
    if(isset($admin_to_emails[$order_status_id]) && $admin_to_emails[$order_status_id] != ''){
      $is_admin_email = true;
    }

    $admin_email_matches = [];
    if (strlen($admin_email) > 0 && $is_admin_email) {
      preg_match_all('/__(.*?)__/', $admin_email, $admin_email_matches);
      if (count($admin_email_matches[1]) > 0 && count($admin_email_matches[1]) > 0) {
        for ($i=0; $i < count($admin_email_matches[1]); $i++) {

          switch($admin_email_matches[1][$i]) {
            case "CUSTOMERNAME":

            $admin_name = '';
            if (isset($customer['name'])) {

              $admin_name = $customer['name'];
            }

            $admin_email = str_replace($admin_email_matches[0][$i], $admin_name, $admin_email);
            break;

            case "ORDERID":

            $admin_email = str_replace($admin_email_matches[0][$i], $order_number, $admin_email);

            break;

            case "ORDERLINK":
            $admin_id = $order_number;//Crypt::encrypt($order_id);
            // $admin_link = route('order.view', ['orderId' => $admin_id]);
            // $admin_tag = '<a href="' . $admin_link . '">View Order</a>';
            // $admin_email = str_replace($admin_email_matches[0][$i], $admin_link, $admin_email);
            // break;
          }
        }
      }
    }

    if (strlen($admin_email) > 0 && $is_admin_email) {
      //send email
      $error = '';

        //dump($customer->email);
        $admin_objDemo = new \stdClass();
        $admin_objDemo->order_id = $order_number;
        $admin_objDemo->order_status = ($preferred_communication_id == 'en') ? $orderStatus->title_en : $orderStatus->title_ar;
        $admin_objDemo->preferred_communication_id = $preferred_communication_id;
        $admin_objDemo->msg = $admin_email;
        $admin_objDemo->order_date = $order->created_at;
        $admin_objDemo->subject = $admin_subject;
        // $order_id = Crypt::encrypt($order_id);
        // $objDemo->link = route('order.view', ['orderId' => $order_id]);
        try {
          Mail::to(explode(",",$admin_to_emails[$order_status_id]))->send(new MailNotification($admin_objDemo));
        } catch (\Exception $e) {

        }

        //update order log--email sent to customer

        $admin_log['order_id'] = $order_id;
        $admin_log['order_status_id'] = $order_status_id;
        $admin_log['source_id'] = 10; //10 meanns notification generated from system.

        $admin_system_user = \App\Model\User::where('email', 'system@system.com')->first();

        if (is_object($admin_system_user)) {
          $admin_log['user_id'] = $admin_system_user->user_id;
        }

        $admin_log['action'] = 2;
        $admin_orderLog =  new \App\Model\OrderLog();
        $admin_orderLog->add($admin_log);

    }




    if (strlen($email) > 0) {
      //send email
      $error = '';

      if (!isset($customer->email) || strlen($customer->email) <= 0) {
        $error = "Customer Email not found, Email Notification can not be sent.";
      } else {
        //dump($customer->email);
        $objDemo = new \stdClass();
        $objDemo->order_id = $order_number;
        $objDemo->order_status = ($preferred_communication_id == 'en') ? $orderStatus->title_en : $orderStatus->title_ar;
        $objDemo->preferred_communication_id = $preferred_communication_id;
        $objDemo->msg = $email;
        $objDemo->order_date = $order->created_at;
        $objDemo->subject = $subject;
        // $order_id = Crypt::encrypt($order_id);
        // $objDemo->link = route('order.view', ['orderId' => $order_id]);
        $emailTo = $customer->email;
        Mail::to($emailTo)->send(new MailNotification($objDemo));
        //update order log--email sent to customer

        $log['order_id'] = $order_id;
        $log['order_status_id'] = $order_status_id;
        $log['source_id'] = 10; //10 meanns notification generated from system.

        $system_user = \App\Model\User::where('email', 'system@system.com')->first();

        if (is_object($system_user)) {
          $log['user_id'] = $system_user->user_id;
        }

        $log['action'] = 2;
        $orderLog =  new \App\Model\OrderLog();
        $orderLog->add($log);
      }
    }

    if (strlen($sms) > 0) {
      //send sms
      //$mobile = $customer->mobile;
      //dump($sms);
      $oSMS = new \App\Notification\SMS( [$customer->mobile], $sms);
      $code = $oSMS->sendPost();

      if ($code != 1){
        $error = $oSMS->getResponseMessage($code);
        //log error to notifications
        $error_log['request']['order_id'] = $order_id;
        $error_log['request']['customer_id'] = $customer_id;
        $error_log['body'] = $error;
        $error_log['f_name'] = "SMS Notification";

        /*if (isset($code['errorCode']) && $code['errorCode'] != 'ER-00'){

        $error_log['request']['order_id'] = $order_id;
        $error_log['request']['customer_id'] = $customer_id;
        $error_log['body'] = $code['message'];
        $error_log['f_name'] = "SMS Notification";*/

        $errorLog = new \App\Model\NotificationErrorLog();
        $errorLog->add($error_log);
      } else {
        $log['order_id'] = $order_id;
        $log['order_status_id'] = $order_status_id;
        $log['source_id'] = 10; //10 meanns notification generated from system.

        $system_user = \App\Model\User::where('email', 'system@system.com')->first();

        if (is_object($system_user)) {
          $log['user_id'] = $system_user['user_id'];
        }

        $log['action'] = 1;
        $orderLog =  new \App\Model\OrderLog();
        $orderLog->add($log);
      }
    }

    if (strlen($pushNotification) > 0) {
      $for = $orderStatus['pushNotificationTemplate']['for'];
      $push = new \App\Notification\PushNotification();

      $fcm_token = \App\Model\CustomerExtra::select('fcm_token')->where('customer_id', $customer_id)->first();

      if (isset($fcm_token->fcm_token)) {
        // $customer = Customer::where('customer_id', $customer_id)->where('status',1)->whereNotNull('fcmtoken')->get()->first();

        //if (isset($customer)) {
        if (stristr($fcm_token->fcm_token,'_HMS_') == true) {
          $code = $push->sendHms($pushNotification, $order_id, $fcm_token->fcm_token, "order_detail", $customer_id, $order->source_id);
        }else {
          $code = $push->send($pushNotification, $order_id, $fcm_token->fcm_token, "order_detail", $customer_id, $order->source_id);
        }

        if ($code == 0) {
          $error = "An Error ocurred while sending the push notification.";

          $error_log['request']['order_id'] = $order_id;
          $error_log['request']['customer_id'] = $customer_id;
          $error_log['body'] = $error;
          $error_log['f_name'] = "Push Notification";

          $errorLog = new \App\Model\NotificationErrorLog();
          $errorLog->add($error_log);
        } else {
          $log['order_id'] = $order_id;
          $log['order_status_id'] = $order_status_id;
          $log['source_id'] = 10; //10 meanns notification generated from system.

          $system_user = \App\Model\User::where('email', 'system@system.com')->first();

          if (is_object($system_user)) {
            $log['user_id'] = $system_user->user_id;
          }

          $log['action'] = 3; // push notification send to customer.
          $orderLog =  new \App\Model\OrderLog();
          $orderLog->add($log);
        }
        // } else {
        //     $error = "Customer fcm token not found, Notification was not sent.";

        //     $error_log['request']['order_id'] = $order_id;
        //     $error_log['request']['customer_id'] = $customer_id;
        //     $error_log['body'] = $error;
        //     $error_log['f_name'] = "Push Notification";

        //     $errorLog = new \App\Model\NotificationErrorLog();
        //     $errorLog->add($error_log);
        // }

      } else {
        $error = "Customer fcm token not found, Notification was not sent.";

        $error_log['request']['order_id'] = $order_id;
        $error_log['request']['customer_id'] = $customer_id;
        $error_log['body'] = $error;
        $error_log['f_name'] = "Push Notification";

        $errorLog = new \App\Model\NotificationErrorLog();
        $errorLog->add($error_log);
      }
    }

    // Generate Pickup Invoice start
    if($order['order_status_id'] == 14){

      // $path = public_path('invoices/');

      // if($order['delivery_invoice'] == null || !file_exists($path.$order['delivery_invoice'])){
      //   $delivery_pdf = \PDF::loadView('yaa_layouts.invoice', $data);



      //   $rand_delivery_num = rand();
      //   $fileName =  'delivery_invoice_'. $rand_delivery_num . $order["order_number"] . '.' . 'pdf' ;
      //   $update_order_delivery_invoice = Order::where(['order_number' => $order_number])->update(['delivery_invoice' => $fileName]);
      //   $delivery_pdf->save($path . '/' . $fileName);
      // }


    }
    // Generate Pickup Invoice end


  if (strlen($error) > 0) {
    return [
      "code" => 400,
      "message" => $error
    ];
  }

  return [
    "code" => 200,
    "message" => "Notifications has been sent successfully"
  ];
}

public static function sendTripCloseNotificationToDriver($driver_fcm_token, $driver_id, $trip_code) {
  if (isset($driver_fcm_token) && strlen($driver_fcm_token) > 0) {
    $notifyToDriver =  new \App\Notification\PushNotification();
    $title = "Trip Closed";
    $body = 'Trip Has Been Closed Against Trip ID ' . $trip_code;

    $code = $notifyToDriver->send($body, 1, $driver_fcm_token, "TRIP_END", $driver_id, null, $title);
    if ($code == 0) {
      $error = "An Error ocurred while sending the trip close notification to driver.";

      //$error_log['request']['order_id'] = $order_id;
      $error_log['request']['driver_id'] = $driver_id;
      $error_log['body'] = $error;
      $error_log['f_name'] = "Push Notification";

      $errorLog = new \App\Model\NotificationErrorLog();
      $errorLog->add($error_log);
    }
  }
}

public static function sendOrderCancelNotificationToDriver($driver_fcm_token, $driver_id, $order_number) {
  if (isset($driver_fcm_token) && strlen($driver_fcm_token) > 0) {
    $notifyToDriver =  new \App\Notification\PushNotification();
    $title = "Order cancelled";
    $body = 'Order ' . $order_number . ' has been cancelled while in shipped state.';

    $code = $notifyToDriver->send($body, 1, $driver_fcm_token, "basic", $driver_id, null, $title);
    if ($code == 0) {
      $error = "An Error ocurred while sending the order cancel notification to driver.";

      //$error_log['request']['order_id'] = $order_id;
      $error_log['request']['driver_id'] = $driver_id;
      $error_log['body'] = $error;
      $error_log['f_name'] = "Push Notification";

      $errorLog = new \App\Model\NotificationErrorLog();
      $errorLog->add($error_log);
    }
  }
}
}
