<?php

namespace App\Model;

use App\Model;
use App\Validator\SapApi as Validator;

class SapApi extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "sap_api_response";
    protected $fillable = ['request','response','is_processed','body','fname'];
    protected $hidden = ["deleted_at"];

    function add($data) {
        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("sapapi.add", [$ex->getMessage()]);
        }
    }

}