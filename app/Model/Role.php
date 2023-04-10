<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Role as Validator;

class Role extends Model
{
    use Validator;

    protected $primaryKey = "role_id";
    protected $table = "roles";
    protected $fillable = ['role_key', 'role_name', 'url', 'class'];
    protected $attributes = ['status' => 1];
    public $timestamps = false;

    static public function getMenuItems()
    {
    	return Role::where('class', '<>', '')->where('status', 1)->get();
    }

    public static function generateRoleKey($url) {
        //we will use $url for role for generating key
        $key = str_replace(".", "_", $url);
        $exists = Role::where('role_key', $key)->exists();
        
        if (!$exists) {
            return $key;    
        }

        return '';
    }

    public function add($data) {


        if (isset($data['url'])) {
            $data['role_key'] = self::generateRoleKey($data['url']);
        } else {
            Error::trigger("role.add", ['Role url is not defined.']);
            return;
        }

        if (!isset($data['class'])) {
            $data['class'] = '';
        }

		if (!isset($data['role_name']) || $data['role_name'] == '') {
            //create role_name on the basis of url
            $name = str_replace("fm.", "", $data['url']);

            $name = str_replace(".", " ", $name);
            $name = ucwords($name);

            $data['role_name'] = $name;
		} else {
            $data['role_name'] = cleanNameString($data['role_name']);
        }

        $data['type'] = 'fleet'; //role added for fleet

        try {
            return parent::add($data);
        } catch(\Exception $ex) {
            Error::trigger("role.add", ['pdo' => $ex->getMessage()]);
        }
    }

    public function change($data, $role_id) {
        print_r($data); exit;

        if (isset($data['role_name'])) {
            $data['role_name'] = cleanNameString($data['role_name']);
        }

		if (!isset($data['role_name']) || $data['role_name'] == '') {
			//create role_name on the basis of url
            $name = str_replace(".", " ", $url);
            $name = ucwords($name);
		}

        if (!isset($data['class'])) {
            $data['class'] = '';
        }

        try {
            return parent::change($data, $role_id);
        } catch(\Exception $ex) {
            Error::trigger('role.change',  ['pdo' =>$ex->getMessage()]);
        }
    }
}
