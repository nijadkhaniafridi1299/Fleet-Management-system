<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\NotificationErrorLog as Validator;

class NotificationErrorLog extends Model
{
	use Validator;
	protected $primaryKey = 'id';
	protected $table = 'notification_error_logs';
	protected $fillable = ['request','body','f_name'];
	public $timestamps = true;

	public function add($data) {
		if (isset($data['request']) && is_array($data['request'])) {
			$data['request'] = json_encode($data['request'], JSON_UNESCAPED_UNICODE);
		}

		try {
			return parent::add($data);
		} catch(\Exception $ex) {
			Error::trigger("notification.error.log.add", [$ex->getMessage()]);
		}
	}
}