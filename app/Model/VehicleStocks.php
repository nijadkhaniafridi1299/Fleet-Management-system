<?php

namespace App\Model;

use App\Model;
use App\Message\Error;

class VehicleStocks extends Model
{
  
    protected $primaryKey = "vehicle_stock_id";
    protected $table = "vehicle_stocks";
   
    public $timestamps = true;
}
