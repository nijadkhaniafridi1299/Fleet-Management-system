<?php

namespace App\Model;

use App\Model;
use App\Validator\UsersUnitSetting as Validator;
use App\Message\Error;

class UsersUnitSetting extends Model
{
    use Validator;
    protected $primaryKey = "id";
    protected $table = "fm_users_unit_settings";
    protected $fillable = [
        'id', 
        'user_id', 
        'title', 
        'show_in_unit_tooltip', 
        'show_in_work_list', 
        'show_in_dashboard'
    ];
    protected $attributes = ['status' => 1];
    public $timestamps = true;

	function users() {
		return $this->hasMany('App\Model\User', 'user_id','user_id');
	}

    function addSettingsForUser($data, $user_id) {
        $existingSettings = UsersUnitSetting::where('user_id', $user_id)->pluck('title')->toArray();
        foreach($data as $setting) {
            if (in_array($setting['title'], $existingSettings)) {
                //setting exists, so modify it.
                try {
                    $set = ['show_in_unit_tooltip' => $setting['show_in_unit_tooltip'], 'show_in_work_list' => $setting['show_in_work_list'], 'show_in_dashboard' => $setting['show_in_dashboard']];
                    UsersUnitSetting::where('user_id', $user_id)->where('title', $setting['title'])->update($set);
                } catch(\Exception $ex) {
                    Error::trigger("usersunitsetting.add", [$ex->getMessage]);
                }
                
            } else {
                //add new setting
                $setting['user_id'] = $user_id;
                $unit_setting = $this->add($setting);
            }
        }
    }

    function add($data) {

		$data['title'] = cleanNameString($data['title']);

		if (!isset($data['title']) || $data['title'] == '') {
			Error::trigger("usersunitsetting.add", ["Please Enter title in English. Special Characters are not allowed."]);
			return false;
		}

		if (isset($data['user_id'])) {
			$data['user_id'] = (int) $data['user_id'];
		} else {
			Error::trigger('usersunitsetting.add', ["User is not specified for unit settings."]);
			return false;
		}

		try {
			$user_setting =  parent::add($data);
			return $user_setting;
		}
		catch(\Exception $ex){
			Error::trigger("usersunitsetting.add", [$ex->getMessage()]);
			return [];
		}
    }

    function change(array $data, $user_unit_setting_id){
		$data['title'] = cleanNameString($data['title']);

		if (!isset($data['title']) || $data['title'] == '') {
			Error::trigger("usersunitsetting.change", ["Please Enter title in English. Special Characters are not allowed."]);
			return false;
		}

		if (isset($data['user_id'])) {
			$data['user_id'] = (int) $data['user_id'];
		} else {
			Error::trigger('usersunitsetting.change', ["User is not specified for unit settings."]);
			return false;
		}

		//dd($data);
		try {
			$user_setting = parent::change($data, $user_unit_setting_id);
			return $user_setting;
		} catch(Exception $ex) {
			Error::trigger("usersunitsetting.change", [$ex->getMessage()]);
		}
  	}
}
