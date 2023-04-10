<?php

namespace App\Model;
use App\Model;
use App\Message\Error;
use App\Validator\Constraints as Validator;

class Constraints extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "constraints";
    protected $fillable = ['trip_id', 'location_level_id', 'key'];
    public $timestamps = true;


    function locations()
    {
        return $this->belongsTo('App\Model\Location', 'location_id');

    }
}
