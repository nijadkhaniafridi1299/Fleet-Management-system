<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\VehicleRoutes as Validator;

class VehicleRoutes extends Model
{
      use Validator;

      protected $primaryKey = "trip_route_id";
      protected $table = "vehicle_routes";
      protected $fillable = [
          'delivery_trip_id',
          'coordinates',
       
      ];


}
