<?php

namespace App\Model;

use App\Model;
use App\Validator\Planning as Validator;
use DB;
use Auth;

class Planning extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "planning";
    protected $fillable = ['health_safety','order_id','safety_orientation','working_days','working_hours','trucks','trips','documentation','doc_description','doc_remarks'];

}
