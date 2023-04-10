<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CancelReason as Validator;
use DB;

class CancelReason extends Model{

    use Validator;

    protected $primaryKey = "cancel_reason_id";
    protected $table = "cancel_reasons";
    protected $fillable = ['reason', 'erp_id', 'status', 'mobile_visible', 'client_sort_id', 'company_id'];
    protected $attributes = ["status" => 1];
    public $timestamps = true;

    protected static $columns = [
        "reason" => "Reason",
        "status" => "Status"
    ];

    public static function getTableColumns() {
        return self::$columns;
    }

    static function getCancelReasons(){
        $reasons = [];
        $CancelReason = new CancelReason;
        $cancelReasons = $CancelReason->where('status',1)->orderby('cancel_reason_id','ASC')->get()->toArray();
        for($i=0, $count = count($cancelReasons); $i < $count; $i++){
            $reasons[] = [
                "cancel_reason_id" => $cancelReasons[$i]['cancel_reason_id'],
                "reason" => json_decode($cancelReasons[$i]['reason'], true),
            ];
        }
        return response()->json([
            "code" => 200,
            "data" => [
                "cancel_reasons" => $reasons
            ]
        ]);
    }




}
