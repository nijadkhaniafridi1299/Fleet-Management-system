<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Trailer as Validator;

class Trailer extends Model
{
    use Validator;

    protected $primaryKey = "trailer_id";
    protected $table = "fm_trailers";
    protected $fillable = [
        'title',
        'desc',
        'ibutton',
        'rfid',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $attributes = ['status' => 1];

    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("trailer.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $trailer_id) {

        try {
            return parent::change($data, $trailer_id);
        }
        catch(Exception $ex) {
            Error::trigger("trailer.change", [$ex->getMessage()]);
        }
    }
}
