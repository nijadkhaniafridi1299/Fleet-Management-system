<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\OrderStatus as Validator;

class OrderStatus extends Model
{
    use Validator;
    protected $primaryKey = "order_status_id";
    protected $table = "order_statuses";
    protected $fillable = ['order_status_title', 'status', 'key', 'company_id'];
    protected $attributes = ["status" => 1];
    public $timestamps = false;
    protected static $columns = [
        "order_status_title" => "Order Status",
        "status" => "Status"
    ];

    public static function getTableColumns() {
        return self::$columns;
    }
    public function emailTemplate() {
        return $this->belongsTo('\App\Model\Template', 'email_template', 'id');
    }
    public function adminEmailTemplate() {
        return $this->belongsTo('\App\Model\Template', 'admin_template', 'id');
    }

    public function smsTemplate() {
        return $this->belongsTo('\App\Model\Template', 'sms_template', 'id');
    }

    public function pushNotificationTemplate() {
        return $this->belongsTo('\App\Model\Template', 'push_notification_template', 'id');
    }

    public function invoiceTemplate() {
        return $this->belongsTo('\App\Model\Template', 'invoice_template', 'id');
    }
}
