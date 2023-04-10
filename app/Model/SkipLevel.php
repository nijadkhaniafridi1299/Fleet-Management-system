<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\SkipLevel as Validator;

class SkipLevel extends Model
{
    use Validator;

    protected $primaryKey = "skip_level_id";
    protected $table = "skip_levels";
    protected $fillable = [
        'skip_level',
        'color',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected $attributes = ['status' => 1, 'created_by'=>0];

    function skips() {
        return $this->hasMany('App\Model\Skip', 'current_skip_level_id', 'skip_level_id');
    }

    function add($data) {
        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("skiplevel.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $skip_level_id) {

        try {
            return parent::change($data, $skip_level_id);
        }
        catch(Exception $ex) {
            Error::trigger("skiplevel.change", [$ex->getMessage()]);
        }
    }
}
