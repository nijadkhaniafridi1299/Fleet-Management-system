<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Category as Validator;

class Category extends Model
{
    use Validator;

 
    protected $primaryKey = "category_id";
    protected $table = "categories";
    protected $fillable = ['parent_id', 'category_name', 'category_description', 'category_status','image','web-image','icon','category_sort'];
    protected $attributes = ['parent_id'=> Null, "category_status" => 1, 'category_description' => Null];
    public $timestamps = true;

    function children(){
    return $this->hasMany('App\Model\Category', 'parent_id')->orderBy('category_sort','ASC');
  }

  function children_active(){
    return $this->hasMany('App\Model\Category', 'parent_id')->where('category_status',1)->orderBy('category_sort','ASC');
  }

  function children_sort(){
    return $this->hasMany('App\Model\Category', 'parent_id')->orderBy('category_sort','ASC');
  }

  function parent(){
    return $this->belongsTo('App\Model\Category', 'parent_id','category_id');
  }
  
}
