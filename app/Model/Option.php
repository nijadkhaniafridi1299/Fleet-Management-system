<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Option as Validator;

class Option extends Model
{
    use Validator;

    protected $primaryKey = "option_id";
    protected $table = "options";
    protected $fillable = ['option_id', 'parent_id', 'option_group', 'option_name', 'option_key', 'option_value', 'option_description', 'option_meta'];
    // protected $attributes = ['status'=> 1, "discount" => 1, 'total' => 0.00, 'is_favourite' => 1];
    public $timestamps = false;

    public function parent() {
        return $this->belongsTo('\App\Model\Option', 'parent_id', 'option_id');
    }

    function change(array $data, $option_id){

        //dd($data);
        //$option = static::find($option_id);

        //$customer->name = json_encode($data['name'], JSON_UNESCAPED_UNICODE);
        if (isset($data['parent_id'])) {
            $data['parent_id'] = (int) $data['parent_id'];
        }

        if (isset($data['option_group'])) {
            $data['option_group'] = (string) $data['option_group'];
        }

        if (isset($data['option_name'])) {
            $data['option_name'] = (string) $data['option_name'];
        }

        if (isset($data['option_key'])) {
            $data['option_key'] = (string) $data['option_key'];
        }

        if (isset($data['option_value'])) {
            $data['option_value'] = (string) $data['option_value'];
        }

        if (isset($data['option_description'])) {
            $data['option_description'] = (string) $data['option_description'];
        } else {
            $data['option_description'] = '';
        }

        if (isset($data['option_meta'])) {
            $data['option_meta'] = (string) $data['option_meta'];
        } else {
            $data['option_meta'] = '';
        }
        //   $option->parent_id = (int) $data['parent_id'];
        //   $option->option_group = (string) $data['option_group'];
        //   $option->option_name = (string) $data['option_name'];
        //   $option->option_key = (string) $data['option_key'];
        //   $option->option_value = (string) $data['option_value'];
        //   $option->option_description = (string) $data['option_description'];
        //   $option->option_meta = (string) $data['option_meta'];
        try {
            //$option->save();
            $option = parent::change($data, $option_id);
            return $option->toArray();
        }
        catch(\Exception $ex){
            Error::trigger("option.change", [$ex->getMessage()]) ;
        }
    }


    static function getValueByKey($key){
        $option = static::where("option_key", $key)->first();
        if(is_object($option)){
            return $option->option_value;
        }
    }

    static function getAllOptions(){
        $constants = [];
        $options = static::get()->toArray();
        for($i=0, $count = count($options); $i < $count; $i++){
            $constants[$options[$i]['option_key']] = $options[$i]['option_value'];
        }

        return $constants;
    }

    static function getGroup($key){
        $data = [];
        $option = static::where("option_key", $key)->first();
        $children = static::where("parent_id", $option->option_id)->get()->toArray();
        for($i=0, $count = count($children); $i < $count; $i++){
            $data[$option->option_key][$children[$i]->option_key] = $children[$i]->option_value;
        }
    }


    static function getGroupInfo($key){
        $data = [];
        $option = static::where("option_key", $key)->first();

        $children = static::where("parent_id", $option->option_id)->where('option_value',1)->get()->toArray();

        for($i=0, $count = count($children); $i < $count; $i++){
            $data[$option->option_key][$children[$i]['option_key']] = $children[$i];
        }

        return $data;
    }

    static function getGroupInfoAll($key){
        $data = [];
        $option = static::where("option_key", $key)->first();

        $children = static::where("parent_id", $option->option_id)->get()->toArray();

        for($i=0, $count = count($children); $i < $count; $i++){
            $data[$option->option_key][$children[$i]['option_key']] = $children[$i];
        }

        return $data;
    }

    static function getAllGroups($parent_id = NULL, $data = []) {

        $options = static::where("parent_id", $parent_id)->get();

        for ($i=0, $count = count($options); $i < $count; $i++) {

            $data[$options[$i]->option_key]['value'] = $options[$i]->option_value;
            $data[$options[$i]->option_key]['children'] = self::getAllGroups($options[$i]->option_id, $data[$options[$i]->option_key]);
        }

        return $data;
    }



}
