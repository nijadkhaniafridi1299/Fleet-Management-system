<?php
namespace App\Notification;

use App\Model\Notification as Notify;
use App\Model\Customer;
use App\Model\CustomerExtra;
use App\Model\Order;
use GuzzleHttp;

// include_once (base_path().'\hms\src\example\push_common\test_sample_push_msg_common.php');
// include_once (base_path().'\hms\src\push_admin\Constants.php');
// use push_admin\Constants;

// use Hms\src\example\push_common\test_sample_push_msg_common\TestPushMsgCommon;


class PushNotification extends Notify{

	function __construct()
	{
		//$this->access_token = 'AIzaSyD0xj_BDsolzQ8mIKE6rADJpa_z7t0biHg';
	}

	public function sendnotificationToCustomer($customer_id, $order_code=null, $message ='', $type='', $origin_id = null){
		//get_customer_id

		$order = Order::where('order_number',$order_code)->get()->first();
		if($order){
			$customer = Customer::where('customer_id',$customer_id)->where('status',1)->whereNotNull('fcm_token')->get()->first();
			if($customer){
				$notifyStatus = $this->send($message, $order_code,$customer->fcm_token, $customer->customer_id, $origin_id);
			}else{
				return 0 ;
			}
		}else{
			return 0 ;
		}
	}

	public function send($body, $order_id, $token = null, $type = null, $user_id = null, $origin_id = null, $title = null) {
		$reg_id = $token;
		$dataArray = array(
			'reference_id' => $order_id,
			'key' => $type,
		);
		if (!isset($title)) {
			$title = '';
		}

		$message = [
			'notification' => [
				'title' => $title,
				'body' => $body,
				'sound' => true
			],
			'data' => $dataArray,
			'to' => $reg_id
		];
		//dd(json_encode($message));
		$client = new GuzzleHttp\Client([
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'key=AAAAYk9ARIg:APA91bFNQHg5ht39hqifldye3XIXFKhZPgmr1e10PTj4CcbclNy3rrlpnOAvLpqOkwU89vIsgfHP8qb2RUfBaapBvleujvF9ESUXpUdxC4MMTyduyIp20VxK9vuCDxlpAIHZbdCens3W',
			]
		]);
		//'Authorization' => 'key=AIzaSyD0xj_BDsolzQ8mIKE6rADJpa_z7t0biHg',

		$response = $client->post('https://fcm.googleapis.com/fcm/send',
		['body' => json_encode($message)]);

		//$resp = json_decode($response->getBody()->getContents(),true);
		if($response->getStatusCode() == 200){
			$resp = json_decode($response->getBody()->getContents(),true);
			//dd($resp);
			if ($resp['success'] == '1') {
				$notification_array = array(
					'origin_id' =>  $origin_id,
					'user_id' => $user_id,
					'notification_body' => json_encode($message),
					'reference_id' => $order_id,
					'type'=> $type,
					'is_sent'=>'true');
			} else {
				$notification_array = array(
					'origin_id' =>  $origin_id,
					'user_id' => $user_id,
					'notification_body' => json_encode($message),
					'reference_id' => $order_id,
					'type'=> $type,
					'is_sent'=> 'false');
			}
			$model = new Notify();
			$model->saveNotification($notification_array);
			return $resp['success'];
		}
		return 0;
	}

	/* Ayesha 28-6-2021
		send notification to driver
	*/

	public function sendCancelNotificationToDriver($orderId, $storeId, $delivery_trip) {

		$message = [
			'delivery_trip_id' => $delivery_trip['delivery_trip_id'],
			'trip_date' => "",
			'vehicle_id' => "",
			'removedOrders' => [$orderId],
			'addedOrders' => [],
			'flag' => true
		];

		$message = json_encode($message);
		//dd($message);

		$curl = curl_init();

		$api_url = 'https://controltower.yaafoods.com/public/index.php/api/tower/v1/update-delivery-trip/'; // production
		// $api_url = 'http://94.74.92.41/yaafoods-back-office/public/api/tower/v1/update-delivery-trip/'; // staging

		curl_setopt_array($curl, array(
		CURLOPT_URL => $api_url . $storeId,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => $message,
		CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3lhYWZvb2RzLWJhY2stb2ZmaWNlXC9wdWJsaWNcL2FwaVwvdXNlclwvbG9naW4iLCJpYXQiOjE2MjQ4ODA4NzgsIm5iZiI6MTYyNDg4MDg3OCwianRpIjoiRml3QkRRblpudWg5RUpyVSIsInN1YiI6NjA3MCwicHJ2IjoiODdlMGFmMWVmOWZkMTU4MTJmZGVjOTcxNTNhMTRlMGIwNDc1NDZhYSIsImNvbXBhbnlfaWQiOiIxIiwiZW1haWwiOiJjdGFkbWluQHlmLmNvbSIsIm5hbWUiOiJ7XCJlblwiOlwiYWRtaW4gQ1RcIixcImFyXCI6XCJhZG1pbiBDVFwifSIsInBob25lIjpudWxsLCJzYWxfb2ZmX2lkIjoxMDAwLCJtYXBfaW5mbyI6eyJsYXRpdHVkZSI6IjI0LjUzNDk2NCIsImxvbmdpdHVkZSI6IjQ2Ljg4NjIwNSJ9fQ.cJ2_yUMlqXGy7Gh2lHlDt4rreZpOCcEMuLeJRrUoh1w',
			'Content-Type: application/json'
		),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$resp = json_decode($response, true);

		// $client = new GuzzleHttp\Client([
		// 	'Content-Type' => 'application/json',
		// 	'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3lhYWZvb2RzLWJhY2stb2ZmaWNlXC9wdWJsaWNcL2FwaVwvdXNlclwvbG9naW4iLCJpYXQiOjE2MjQ4NzU4NzYsIm5iZiI6MTYyNDg3NTg3NiwianRpIjoiejloNkc2SU8zUWhua1hBTSIsInN1YiI6NjA3MCwicHJ2IjoiODdlMGFmMWVmOWZkMTU4MTJmZGVjOTcxNTNhMTRlMGIwNDc1NDZhYSIsImNvbXBhbnlfaWQiOiIxIiwiZW1haWwiOiJjdGFkbWluQHlmLmNvbSIsIm5hbWUiOiJ7XCJlblwiOlwiYWRtaW4gQ1RcIixcImFyXCI6XCJhZG1pbiBDVFwifSIsInBob25lIjpudWxsLCJzYWxfb2ZmX2lkIjoxMDAwLCJtYXBfaW5mbyI6eyJsYXRpdHVkZSI6IjI0LjUzNDk2NCIsImxvbmdpdHVkZSI6IjQ2Ljg4NjIwNSJ9fQ.GPFRpTVupJJraSlMzfOtYY7CajnBoFvmwa8R0-18tkQ'
		// ]);

		// $response = $client->post('http://94.74.92.41/yaafoods-back-office/public/api/tower/v1/update-delivery-trip/' . $storeId,
		// ['body' => $message]);

		// $resp = json_decode($response->getBody()->getContents(), true);
		if ($resp['code'] == 200) {
			return 1;
		}

		return 0;
	}

	public function notify($preferred_lang, $order_code, $token = null, $type = null, $user_id = null, $origin_id = null)
	{
		//dd($origin_id);
		$body = \App\Models\TblTemplate::where('for',$type)->get($preferred_lang)->first();
		// dd($body);

		/*		$body = str_replace('__STAFFNAME__','talha',$body);
		*/
		$reg_id = $token;
		$dataArray = array(
			'reference_id' => $order_code,
			'key' => $type,

		);

		switch ($type) {
			case 'customer_placed_order':
				$title = 'Customer Has Placed An Order';
				$body = $body->en;
				$body = str_replace('__ORDERID__',$order_code,$body);
				//$body = 'Order Placed!';
				break;
			case 'customer_cancelled_service':
				$title = 'Customer Has Cancelled a Service';
				//$service_name = \App\Models\TblAppointmentService::with('_services','_services.tbl_services')->where('app_service_ref_no',$order_coder)->get()->toArray();
				//$serv_name = json_decode($service_name[0]['_services']['tbl_services']['service_name'],true)[$preferred_lang];
				$body = $body->en;
				///$body = str_replace('__SERVICEID__',$order_code,$body);
				//$body = str_replace('__SERVICENAME__',$serv_name,$body);
				break;
			case 'manager_cancelled_order'	;
				$title = 'Manager has Cancelled Your Service';
				//$service_name = \App\Models\TblAppointmentService::with('_services','_services.tbl_services')->where('app_service_ref_no',$order_code)->get()->toArray();
				//$serv_name = json_decode($service_name[0]['_services']['tbl_services']['service_name'],true)[$preferred_lang];
				$body = $body->en;
				//$body = str_replace('__SERVICEID__',$order_code,$body);
				//$body = str_replace('__SERVICENAME__',$serv_name,$body);
				break;
			case 'staff_completed_service':
				$title = 'Staff Has Completed Service';
				$service_name = \App\Models\TblAppointmentService::with('_services','_services.tbl_services')->where('app_service_ref_no',$order_code)->get()->toArray();
				$serv_name = json_decode($service_name[0]['_services']['tbl_services']['service_name'],true)[$preferred_lang];

				$staff = \App\Models\User::where('user_id',$staff_id)->get()->toArray();
				$staff_name = $staff[0]['name'];

				$body = $body->en;
				$body = str_replace('__SERVICEID__',$order_code,$body);
				$body = str_replace('__SERVICENAME__',$serv_name,$body);
				$body = str_replace('__STAFFNAME__',$staff_name,$body);
				break;
			case 'staff_started_service':
				$title = 'Staff Started Service';
				$service_name = \App\Models\TblAppointmentService::with('_services','_services.tbl_services')->where('app_service_ref_no',$order_code)->get()->toArray();
				$serv_name = json_decode($service_name[0]['_services']['tbl_services']['service_name'],true)[$preferred_lang];

				$staff = \App\Models\User::where('user_id',$staff_id)->get()->toArray();
				$staff_name = $staff[0]['name'];

				$body = $body->en;
				$body = str_replace('__SERVICEID__',$order_code,$body);
				$body = str_replace('__SERVICENAME__',$serv_name,$body);
				$body = str_replace('__STAFFNAME__',$staff_name,$body);
				break;
			case 'customer_cancelled_order':
				$title = 'Customer Cancelled Order';
				$service_name = \App\Models\TblAppointmentService::with('tbl_appointment','tbl_appointment.tbl_customer')->where('app_service_ref_no',$order_code)->get()->toArray();
				// dd($service_name);
				$customer_name = $service_name[0]['tbl_appointment']['tbl_customer']['customer_name'];

				$body = $body->en;
				$body = str_replace('__ORDERID__',$order_code,$body);
				$body = str_replace('__CUSTOMER__',$customer_name,$body);
				break;
			case 'staff_cancelled_order';
				$title = 'Staff Cancelled Order';
				$service = \App\Models\TblAppointmentService::with('tbl_user')->where('app_service_ref_no',$order_code)->get()->toArray();
				$app_service = \App\Models\TblAppointmentService::with('tbl_appointment')->where('app_service_ref_no',$order_code)->get()->toArray();
				// dd($service_name);
				$user_name = $service[0]['tbl_user']['name'];
				$order_number = $app_service[0]['tbl_appointment']['app_ref_no'];

				$body = $body->en;
				$body = str_replace('__ORDERID__',$order_code,$body);
				$body = str_replace('__STAFFNAME__',$user_name,$body);
				break;
			case 'manager_confirmed_order';
				$title = 'Manager Confirmed Order';
				// $app = \App\Models\TblAppointmentService::with('tbl_appointment')->where('app_service_ref_no',$order_code)->toArray();

				// $staff = \App\Models\User::where('user_id',$staff_id)->get()->toArray();
				// $staff_name = $staff[0]['name'];

				$body = $body->$preferred_lang;
				$body = str_replace('__ORDERID__',$order_code,$body);
				// $body = str_replace('__CUSTOMERNAME__',$serv_name,$body);
				break;
			case 'staff_confirmed_order':
				$title = 'Staff Confirmed Order';

				// $staff = \App\Models\User::where('user_id',$staff_id)->get()->toArray();
				// $staff_name = $staff[0]['name'];

				$body = $body->$preferred_lang;
				$body = str_replace('__ORDERID__',$order_code,$body);
				// $body = str_replace('__CUSTOMERNAME__',$app[0]['tbl_appointment']['tbl_cutomer']['customer_name'],$body);
				break;
			default:
			break;
		}
		$message = [
			'notification' => [
				'title' => $title,
				'body' => $body,
				'sound' => true
			],
			'data' => $dataArray,
			'to' => $reg_id
		];
		//dd($message);
		$client = new GuzzleHttp\Client([
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'key=AIzaSyD0xj_BDsolzQ8mIKE6rADJpa_z7t0biHg',
			]
		]);

		$response = $client->post('https://fcm.googleapis.com/fcm/send',
		['body' => json_encode($message)]
		);

		//$resp = json_decode($response->getBody()->getContents(),true);
		if($response->getStatusCode() == 200){
			$resp = json_decode($response->getBody()->getContents(),true);
			//dd($resp);
			if($resp['success'] == '1'){
				$notification_array = array(
					'origin_id' =>  $origin_id,
					'user_id' => $user_id,
					'notification_body' => json_encode($message),
					'reference_id' => $order_code,
					'type'=> $type,
					'is_sent'=>'true');
			} else {
				$notification_array = array(
					'origin_id' =>  $origin_id,
					'user_id' => $user_id,
					'notification_body' => json_encode($message),
					'reference_id' => $order_code,
					'type'=> $type,
					'is_sent'=> 'false');
			}
			//dd($notification_array);
			$model = new Notify();
			$model->saveNotification($notification_array);
			return $resp['success'];
		}
		return 0;
	}

	public function sendHms($body, $order_id, $token = null, $type = null, $user_id = null, $origin_id = null){
		$auth_token = $this->getHmsToken();

		$title = 'Yaa Notification';
		$data = ['reference_id'=>$order_id,'key'=>$type];
		$message = [
			"message"=> [
				"android"=> [
					"notification"=> [
						"title"=> $title,
						"body"=> $body,
						"click_action"=> [
							"type"=> 3
						]
					]
				],
				"data"=>"{'reference_id':'{$order_id}','key':'{$type}'}",
				"token"=> [$token]
			]
		];

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://push-api.cloud.huawei.com/v1/103178941/messages:send',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>json_encode($message),
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer {$auth_token}",
				"Content-Type : application/json"
			],
		));

		$response = curl_exec($curl);
		$response = json_decode($response,true);
		curl_close($curl);

		if ($response['code'] == '80000000') {
			$notification_array = array(
				'origin_id' =>  $origin_id,
				'user_id' => $user_id,
				'notification_body' => json_encode($message),
				'reference_id' => $order_code,
				'type'=> $type,
				'is_sent'=>'true');
		} else {
			$notification_array = array(
				'origin_id' =>  $origin_id,
				'user_id' => $user_id,
				'notification_body' => json_encode($message),
				'reference_id' => $order_code,
				'type'=> $type,
				'is_sent'=> 'false');
		}
		$model = new Notify();
		$model->saveNotification($notification_array);
		return 1;
	}


	function getHmsToken(){

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://oauth-login.cloud.huawei.com/oauth2/v3/token',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => 'grant_type=client_credentials&client_id=103178941&client_secret=6c207e22ed21eefe44aec22e7a0539f909753e5f5e31ef42786ab7df3f5f8cfe',
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded'
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		try {
			$response = json_decode($response,true);
			return isset($response['access_token'])?$response['access_token']:0;
		} catch (\Exception $e) {
			return 0;
		}
	}

	function createOrderNotification($customer_id, $order_id)
    {


        $notification_id =  Customer::where('customer_id', $customer_id)->value('fcm_token');

        if ($notification_id != "" || $notification_id != null) {

            $title = "Order Created";

            $message = "Your Work Order has been placed against Order " . $order_id;
            $id = $customer_id;
            $type = "basic";

            $res = send_notification_FCM($notification_id, $title, $message, $id, $type);
			
        } else {
            return;
        }
		
    }

	function createOrderNotificationAdmin($customer_id, $order_id, $customer_name)
    {


		$admin_id = \App\Model\User::where('group_id',19)->value('user_id');
        $notification_id =  \App\Model\User::where('user_id', $admin_id)->value('fcm_token_for_web');

        if ($notification_id != "" || $notification_id != null) {

            $title = "Order Created";

            $message = $customer_name . " has placed Work Order against order code " . $order_id;
            $id = $customer_id;
            $type = "basic";

            $res = send_notification_FCM($notification_id, $title, $message, $id, $type);
			
        } else {
            return;
        }
		
    }


}
