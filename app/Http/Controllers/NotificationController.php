<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Notification;
use App\Message\Error;

class NotificationController extends Controller
{
    public function getAllNotifications(Request $request, $yard_id = null)
    {
        $errors = [];
        $data = $request->json()->all();
        if(count($data)==0){ $data = $request->all(); }
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        // if($yard_id == null){ $yard_id = config('yms.yard_id'); }

        $res_data = array();

        $notifications = Notification::where('user_id',$data['created_by'])->where("to_source",$data['created_source'])->orderBy("is_read")->orderBy("created_at")->get()->toArray();

        $read_notifications = Notification::where('user_id',$data['created_by'])->where("is_read",0)->where("to_source",$data['created_source'])->get()->toArray();

        if (isset($errors) && count($errors) > 0) {
            return respondWithError($errors,$request_log_id,404);
        }

        $res_data['notifications'] = $notifications;
        $res_data['total_count'] = count($notifications);
        $res_data['unread_count'] = count($read_notifications);
        // count to be return additionally
        // return respondWithSuccess($res_data, 'SUPPLIER', $request_log_id, "");
        return response()->json([
            "Code" => 200,
            "data" => ["res_data" => $res_data,
            "request_log_id" => $request_log_id
            ],
            "message" => "Notification data fetched successfully successfully."
            ]);
        }
        
    

    public function setNotificationAsRead(Request $request, $notification_id, $yard_id = null){
        $errors = [];
        $data = $request->json()->all();
        if(count($data)==0){ $data = $request->all(); }
        $request_log_id = $data['request_log_id'];
        unset($data['request_log_id']);

        // if($yard_id == null){ $yard_id = config('yms.yard_id'); }
        $notify = Notification::find($notification_id);

        if (!is_object($notify)) {
            Error::trigger("notifications.list", ["Notification List is empty."]);
            $errors = \App\Message\Error::get('notifications.list');
        }

        if (isset($errors) && count($errors) > 0) {
            return respondWithError($errors,$request_log_id,203);
        }

        // find($notification_id);
        $notify->is_read = 1;
        $notify->save();
        $res_data = array();
        $res_data['notification'] = $notify;

        return response()->json([
            "Code" => 200,
            "data" => ["res_data" => $res_data,
            "request_log_id" => $request_log_id
            ],
            "message" => "Marked as read successfully."
            ]);
    }
}