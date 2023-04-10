<?php

namespace App\Model;
use App\Model;
use App\Validator\GeoFence as Validator;

class GeoFence extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "fm_geofences";
    public $timestamps = true;
    protected $fillable = [
        'name',
        'zonegroup',
        'type',
        'radius',
        'latlong',
        'switch',
        'created_at',
        'updated_at',
        'deleted_at'
       
    ];
    protected $casts = [
        'switch' => 'boolean',
     ];

     function vehicles() {
        return $this->hasMany('\App\Model\Vehicle', 'geofence_id', 'id');
    }

     function vehicle_groups() {
        return $this->hasMany('\App\Model\VehicleGroup', 'geofence_id', 'id');
    }

    // return $this->hasManyThrough(
    //     'App\Post',
    //     'App\User',
    //     'country_id', // Foreign key on users table...
    //     'user_id', // Foreign key on posts table...
    //     'id', // Local key on countries table...
    //     'id' // Local key on users table...
    // );

    function add($data) {

        $data['coordinates'] = array_filter($data['coordinates']);
        $data['coordinates'] = json_encode($data['coordinates']);
        
        unset($data['vehicle_group_id']);
        unset($data['vehicle_ids']);

        //echo print_r($data, true); exit;
        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("geofence.add", [$ex->getMessage()]);
        }
    }





}
