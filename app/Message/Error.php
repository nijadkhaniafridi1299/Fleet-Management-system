<?php

namespace App\Message;
use App\Message;

class Error extends Message{

    protected static $data;

    static function trigger($key, $data){
        parent::push("error.{$key}", $data);
    }

    static function get($key, $default = NULL){
        return parent::get("error.{$key}", $default);
    }
}
