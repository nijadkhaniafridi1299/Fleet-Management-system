<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\IoTButton as Validator;

class IoTButton extends Model
{
	use Validator;
	protected $primaryKey = 'iot_device_id';
	protected $table = 'iot_buttons';
	protected $fillable = [
		'customer_id',
		'imei',
		'address_id',
		'sim_card_number',
		'connection_state',
		'asset_id',
		'status'
	];
	public $timestamps = true;
}