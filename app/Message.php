<?php

namespace App;

class Message{

    protected static $data = [];

    static function push($key, $data){
        static::$data[$key] = $data;
    }

    static function has($key){
        return isset(static::$data[$key]);
    }

    static function get($key, $default = NULL){
        if(static::has($key)) return static::$data[$key];
        else return $default;
    }

    static function show(){
        echo '<pre>'.print_r(static::$data, true).'</pre>';
    }
}
