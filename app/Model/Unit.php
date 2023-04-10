<?php

namespace App\Model;

use App\Model;
use App\Validator\Unit as Validator;
use DB;
use Auth;

class Unit extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "units";
    protected $fillable = ['unit','status'];

}
