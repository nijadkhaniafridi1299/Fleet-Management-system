<?php

namespace App\Model;
use App\Model\MobileRequest as AppRequest;
use App\Message\Error;

class TempRequest extends AppRequest{

    public function AddRequest($parm)
    {
    $errors = array();
    $req = $this->add($parm);

    if(!is_object($req)){
        $errors[] = Error::get('request.add'); //print_r($errors);exit;
    }
    else {
        return $req;
    }

    }

    public function UpdateRequest($reqId){
        $errors = array();

        $req = AppRequest::where("id", $reqId)->first();

        if(is_object($req)){

            $req->record_status = 1 ;

            try{
                 $req->save();
                return $req;
            }
            catch(\Exception $ex){
                Error::trigger('request.status', ["db" => $ex->getMessage()]);
                return false;
            }

        }
        else{
            Error::trigger('request.status', ["request_id"=>"request not found with request id {$reqId}"]);
            return false;
        }
    }

    public function UpdateRequestByTransId($transId){
        $errors = array();

        $req = AppRequest::where("transaction_id", $transId)->first();

        if(is_object($req)){

            $req->record_status = 1 ;
            $req->payment_status = 1 ;

            try{
                 $req->save();
                return $req;
            }
            catch(\Exception $ex){
                Error::trigger('request.status', ["db" => $ex->getMessage()]);
                return false;
            }

        }
        else{
            Error::trigger('request.status', ["request_id"=>"request not found with request id {$transId}"]);
            return false;
        }
    }
}
