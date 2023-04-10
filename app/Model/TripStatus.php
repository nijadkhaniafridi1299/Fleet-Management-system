<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\TripStatus as Validator;
use DB;

class TripStatus extends Model
{
    use Validator;
    protected $primaryKey = "trip_status_id";
    protected $table = "trip_statuses";
    public $timestamps = true;
    protected $fillable = 
    [
    'trip_status_title',
     'status',
      'created_by',
       'trip_status_meta',
        'key', 
        'companies'
    ];
  
   
    

    }
