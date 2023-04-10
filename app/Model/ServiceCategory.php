<?php

namespace App\Model;

use App\Model;
use App\Model\AssetInventory;
use App\Validator\ServiceCategory as Validator;
use DB;
use Auth;

class ServiceCategory extends Model
{
    use Validator;

    protected $primaryKey = "service_category_id";
    protected $table = "service_category";

    protected $fillable = [
        'parent_id',
        'title'
    ];

    
    function model(){
      return ServiceCategory::where('model','<>',null)->pluck('service_category_id');
    }

    function equipment(){
      return ServiceCategory::whereRaw("parent_id IN (SELECT `service_category_id`
      FROM `service_category` WHERE `key`LIKE '%EQUIPMENT%')")->pluck('service_category_id');
    }

    function labor(){
      return ServiceCategory::whereRaw("parent_id IN (SELECT `service_category_id`
      FROM `service_category` WHERE `key`LIKE '%LABORS%')")->pluck('service_category_id');
    }

    function tools(){
      return ServiceCategory::whereRaw("parent_id IN (SELECT `service_category_id`
      FROM `service_category` WHERE `key`LIKE '%TOOLS%')")->pluck('service_category_id');
    }
    
    function assets(){
      return ServiceCategory::whereRaw("parent_id IN (SELECT `service_category_id`
        FROM `service_category` WHERE `key`LIKE '%ASSETS%')")->pluck('service_category_id');
    }

    function items() {
      return $this->hasMany('App\Model\AssetInventory', 'service_category_id', 'service_category_id');
  }

  function parent(){
    return $this->belongsTo('App\Model\ServiceCategory', 'parent_id','service_category_id');
}
}
