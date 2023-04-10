<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Group as Validator;

class Group extends Model
{
    use Validator;

    protected $primaryKey = "group_id";
    protected $table = "groups";
    protected $fillable = ['group_id', 'company_id', 'group_name', 'group_description'];
    protected $attributes = ['status'=>1, 'created_by'=>0];
    public $timestamps = true;

    public function url(){
        return $this->belongsTo('\App\Model\Role', 'url', 'url');
    }
    	protected $casts = [
        'role_id' => 'array'
    ];

    function add($data) {

        if (isset($data['role_id']) && is_array($data['role_id'])) {

            $all_roles = \App\Model\Role::all()->toArray();

            if (count($data['role_id']) == count($all_roles)) {
                $data['role_id'] = "[]";
            } else {
                for ($i=0; $i < count($data['role_id']); $i++) {
                    $data['role_id'][$i] = (int) $data['role_id'][$i];
                }

                $data['role_id'] = json_encode($data['role_id']);
            }  
        } else {
            $data['role_id'] = "[]";
        }

        $user = auth()->user();

        $created_by = '';
        if (isset($user)) {

            if (isset($user['first_name'])) {
                $created_by .= $user['first_name'];
            }

            if (isset($user['last_name'])) {
                if (strlen($created_by) > 0) {
                    $created_by .= " "; //adding space between firstname and lastname
                }

                $created_by .= $user['last_name'];
            }
        }
        //    echo '<pre>'.print_r($data, true).'</pre>'; exit;
        $data['created_by'] = $created_by;

        $data['group_name'] = cleanNameString($data['group_name']);

		if (!isset($data['group_name']) || $data['group_name'] == '') {
			Error::trigger("group.add", ["Please enter name in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

		$data['group_description'] = cleanNameString($data['group_description']);

		if (!isset($data['group_description']) || $data['group_description'] == '') {
			Error::trigger("group.add", ["Please enter description in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

        //$data['group_key'] = 2; //2 is for CallCenter
        //dd($data);
        try {
            $group =  parent::add($data);
            return $group;
        }
        catch(\Exception $ex){

          //echo '<pre>'.print_r($data, true).'</pre>'; exit;
            Error::trigger("group.add", [$ex->getMessage()]) ;
        }
    }

    function change(array $data, $group_id) {

        $data['group_name'] = cleanNameString($data['group_name']);

		if (!isset($data['group_name']) || $data['group_name'] == '') {
			Error::trigger("group.change", ["Please enter name in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

		$data['group_description'] = cleanNameString($data['group_description']);

		if (!isset($data['group_description']) || $data['group_description'] == '') {
			Error::trigger("group.change", ["Please enter description in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

       
        if (isset($data['role_id']) && is_array($data['role_id'])) {

            $all_roles = \App\Model\Role::all()->toArray();
            if (count($data['role_id']) == count($all_roles)) {
                $data['role_id'] = "[]";
            } else {
                for ($i=0; $i < count($data['role_id']); $i++) {
                    $data['role_id'][$i] = (int) $data['role_id'][$i];
                }

                $data['role_id'] = json_encode($data['role_id']);
            }  
        } else {
            $data['role_id'] = "[]";
        }

        try {
            return parent::change($data, $group_id);
        }
        catch(Exception $ex){
            Error::trigger("group.change", [$ex->getMessage()]);
        }
    }
}
