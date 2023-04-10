<?php

namespace App\Model;

use App\Model;
use App\Validator\Labor as Validator;
use DB;
use Auth;

class Labor extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "labor";
    protected $fillable = ['name','designation','nationality'];

}
