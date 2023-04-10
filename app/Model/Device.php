<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use Illuminate\Support\Facades\Hash;
use App\Validator\Device as Validator;

class Device extends Model
{
    use Validator;

    protected $primaryKey = "device_id";
    protected $table = "fm_devices";
    protected $fillable = [
        'device_id',
        'device_password',
        'imei',
        'device_serial',
        'sim_card_number',
        'sim_card_serial',
        'device_protocol_id',
        'connection_state',
        'device_type',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'tracker_id'
    ];

    protected $attributes = ['device_protocol_id' => 0, 'created_by'=>0];

    function device_protocol() {
        return $this->belongsTo('App\Model\DeviceProtocol', 'device_protocol_id', 'device_protocol_id');
    }

    function sensors() {
        return $this->hasMany('\App\Model\Sensor', 'device_id', 'device_id');
    }

    function vehicles() {
        return $this->hasOne('\App\Model\Vehicle', 'device_id', 'device_id');
    }

    function add($data) {

        if (array_key_exists('old_password', $data)) {
            unset($data['old_password']);
        }

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("device.add", [$ex->getMessage()]);
        }
    }


    function change(array $data, $device_id){

        $device = Device::find($device_id);
        if (isset($data['device_password'])) {
			$data['device_password'] = Hash::make($data['device_password']);

            if (array_key_exists('old_password', $data)) {
                unset($data['old_password']);
            }
		}

		if (!isset($data['device_password']) || $data['device_password'] == '' || $data['device_password'] == null) {
			$data['device_password'] = $data['old_password'];
            if (array_key_exists('old_password', $data)) {
                unset($data['old_password']);
            }
		}

        if (isset($data['imei']) && $data['imei'] == $device->imei) {
            unset($data['imei']);
        }

        if (isset($data['sim_card_number']) && $data['sim_card_number'] == $device->sim_card_number) {
            unset($data['sim_card_number']);
        }

        try {
            return parent::change($data, $device_id);
        }
        catch(Exception $ex) {
            Error::trigger("device.change", [$ex->getMessage()]);
        }
    }
}
