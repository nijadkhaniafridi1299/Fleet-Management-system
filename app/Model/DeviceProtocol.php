<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\DeviceProtocol as Validator;

class DeviceProtocol extends Model
{
    use Validator;

    protected $primaryKey = "device_protocol_id";
    protected $table = "fm_device_protocols";
    protected $fillable = [
        'device_protocol_title',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['created_by'=>0];

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("deviceprotocol.add", [$ex->getMessage()]);
        }
    }

    function change($data, $device_protocol_id){

        try{
            return parent::change($data, $device_protocol_id);
        }
        catch(Exception $ex){
            Error::trigger("deviceprotocol.change", [$ex->getMessage()]) ;
        }
    }
}
