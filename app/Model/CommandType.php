<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\CommandType as Validator;

class CommandType extends Model
{
    use Validator;

    protected $primaryKey = "command_type_id";
    protected $table = "fm_command_types";
    protected $fillable = [
        'title',
        'created_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['created_by'=>0];

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("commandtype.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $command_type_id) {

        try {
            return parent::change($data, $command_type_id);
        }
        catch(Exception $ex) {
            Error::trigger("commandtype.change", [$ex->getMessage()]);
        }
    }
}
