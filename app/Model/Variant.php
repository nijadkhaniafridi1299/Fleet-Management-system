<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Variant as Validator;

class Variant extends Model
{
    use Validator;

 
    protected $primaryKey = "variant_id";
    protected $table = "variants";

    public static function getTableColumns() {
        return self::$columns;
    }
}
