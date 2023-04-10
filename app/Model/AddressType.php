<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\AddressType as Validator;
use DB;

class AddressType extends Model{

    use Validator;
    protected $primaryKey = "address_type_id";
    protected $table = "address_types";
    protected $fillable = ['name'];
    protected $attributes = [];
   

   

}
