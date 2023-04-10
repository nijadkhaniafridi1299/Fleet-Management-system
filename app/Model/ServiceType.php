<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ServiceType as Validator;

class ServiceType extends Model
{
    use Validator;

    protected $primaryKey = "service_type_id";
    protected $table = "fm_service_types";
    protected $fillable = [
        'title',
        'interval',
        'last_service',
        'event_left',
        'service_id',
        'status',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['created_by'=>0, 'status' => 1];

    function service() {
        return $this->belongsTo('App\Model\Service', 'service_id', 'service_id');
    }

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("servicetype.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $service_type_id) {

        try {
            return parent::change($data, $service_type_id);
        }
        catch(Exception $ex) {
            Error::trigger("servicetype.change", [$ex->getMessage()]);
        }
    }

    function bulk_insert($data) {

        try {
            self::insert($data);
        } catch(\Exception $ex) {
            Error::trigger("servicetype.add", [$ex->getMessage()]);
        }   
    }
}
