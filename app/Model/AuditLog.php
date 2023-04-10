<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\AuditLog as Validator;

class AuditLog extends Model
{
    use Validator;

    protected $primaryKey = "audit_log_id";
    protected $table = "fm_audit_logs";
    protected $fillable = [
        'request_id',
        'module_id',
        'resource'
    ];
    protected $attributes = ['status' => 1];
   
    function logRequest() {
        return $this->belongsTo('App\Model\FleetRequest', 'id', 'request_log_id');
    }

    function module() {
        return $this->belongsTo('App\Model\FleetModule', 'module_id', 'module_id');
    }

    function add($data) {
        
        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("auditlog.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $vehicle_id){

        try{
            return parent::change($data, $vehicle_id);
        }
        catch(Exception $ex){
            Error::trigger("auditlog.change", [$ex->getMessage()]);
        }
    }
}
