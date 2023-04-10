<?php

namespace App\Model;

use App\Model;
use App\Validator\Designation as Validator;
use DB;
use Auth;

class Designation extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "designations";
    protected $fillable = ['designation'];

}
