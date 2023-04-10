<?php

namespace App\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Model;
use App\Validator\User as Validator;
use Illuminate\Support\Facades\Hash;
use App\Message\Error;

class User extends Model implements AuthenticatableContract, AuthorizableContract,JWTSubject
{
    use Validator, Authenticatable, Authorizable, HasFactory;
    protected $primaryKey = "user_id";
    protected $table = "users";
    protected $fillable = [
        'company_id', 
        'branch_id', 
        'first_name', 
        'last_name', 
        'email', 
        'password', 
        'plain_password', 
        'mobile',
        'gidea_terminal_id',
        'gender', 
        'avatar', 
        'group_id', 
        'rsm_region', 
        'reporting_to', 
        'designation', 
        'status', 
        'last_login', 
        'pass_change',
        'employee_id',
        'role_id',
        'url',
        'driver_cost',
        'name',
        'operations',
        'driver_wallet',
        'roles',
        'user_meta',
        'username',
        'phone',
        'profile_image',
        'salary',
        'country_id',
        'ticket_region',
        'fcm_token_for_driver_app',
        'default_store_id',
        'language', 'timezone', 'notes', 'commercial_number', 'tax_number', 'location_id', 'postal_code', 'address', 'phones', 'emails', 
        'icon', 'show_object_tail', 'object_tail_color', 'remember_last_map_position', 'remember_last_map_zoom',
        'show_zoom_slider_control', 'show_select_tile_control', 'default_selected_tile', 'show_tiles_in_select_tile',
        'show_layers_control', 'show_layers_in_layers_control', 'show_utilities_control', 'show_utilities_in_utilities_control',
        'default_map_center_latitude', 'default_map_center_longitude', 'default_unit_location_latitude', 'default_unit_location_longitude',
        'address_format', 'start_weekday', 'lock_job_order_no', 'volume_unit', 'weight_unit', 'length_unit',
        'count_unit', 'temperature_unit', 'distance_unit',
    ];
    protected $attributes = ['group_id'=>0, 'company_id' => 1, 'branch_id' => Null, 'avatar' => Null, 'pass_change' => Null, 
        'rsm_region' => Null, 'reporting_to' => Null, 'designation' => '', 'status' => 1, 'last_login' => Null, 'role_id' => 0];
    public $timestamps = true;
	// protected $casts = [
    //     'role_id' => 'array'
    // ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
	protected $hidden = ['password','plain_password','pass_change','fcm_token_for_driver_app','fcm_token_for_web','auth_token','created_at','updated_at','deleted_at'];

	function ticket() {
		return $this->hasMany('App\Model\Ticket', 'user_id','user_id');
	}

	function pendingTicket() {
		return $this->hasMany('App\Model\Ticket', 'user_id','user_id')->where('status',1);
	}

	function reportingTo() {
		return $this->hasOne('App\Model\User', 'user_id','reporting_to')->where('status',1);
	}

	function group() {
		return $this->belongsTo('App\Model\Group', 'group_id', 'group_id')->where('status', 1);
	}

	function vehicle() {
		return $this->hasOne('App\Model\Vehicle', 'driver_id', 'user_id')->where('status', 1);
	}

	// function driver() {
	// 	return $this->hasOne('App\Model\Driver', 'user_id', 'user_id');
	// }

	function userUnitSettings() {
		return $this->hasMany('App\Model\UsersUnitSetting', 'user_id', 'user_id');
	}

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    function add($data) {

		$data['first_name'] = cleanNameString($data['first_name']);

		if (!isset($data['first_name']) || $data['first_name'] == '') {
			Error::trigger("user.add", ["Please Enter First name in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

		$data['last_name'] = cleanNameString($data['last_name']);

		if (!isset($data['last_name']) || $data['last_name'] == '') {
			Error::trigger("user.add", ["Please Enter Last Name in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

		if (isset($data['designation'])) {
			$data['designation'] = cleanNameString($data['designation']);

			if (!isset($data['designation']) || $data['designation'] == '') {
				Error::trigger("user.add", ["Please Enter Designation in English/Arabic. Special Characters are not allowed."]);
				return false;
			}
		}

		if (!preg_match("/^\d+$/", $data['employee_id'])) {
			Error::trigger("user.add", ["Please Enter EmployeeId in digits. Characters are not allowed."]);
			return false;
		}
		//check if employee id is unique amoung table or not.
		$user = User::where('employee_id', $data['employee_id'])->first();
		if (is_object($user)) {
			//it means user exists with same employee Id.
			Error::trigger("user.add", ["Entered Employee Id is assigned to another user."]);
			return false;
		}

		if (isset($data['company_id'])) {
			$data['company_id'] = (int) $data['company_id'];
		} else {
			$data['company_id'] = 1;
		}

		if (isset($data['email'])) {
			$data['email'] = (string) $data['email'];
		}

		//$data['plain_password'] = (string) $data['plain_password'];
		if (isset($data['password'])) {
			$data['plain_password'] =  $data['password'];
			$data['password'] = Hash::make($data['password']);
		}

		if (isset($data['old_password'])) {
			unset($data['old_password']);
		}

		if (isset($data['gender'])) {
			$data['gender'] = (string) $data['gender'];
		}

		// if (isset($data['avatar'])) {
		// 	$data['avatar'] = (string) $data['avatar'];
		// }

		if (isset($data['group_id'])) {
			$data['group_id'] = (int) $data['group_id'];
		} else {
			Error::trigger('user.add', ["User Roles are not specified."]);
			return false;
		}

		if (isset($data['rsm_region'])) {
			$data['rsm_region'] = (int) $data['rsm_region'];
		}

		if (isset( $data['reporting_to'])) {
			$data['reporting_to'] = (int) $data['reporting_to'];
		}

		if (isset($data['status'])) {
			$data['status'] = (int) $data['status'];
			if($data['status'] != 1){
				$data['status'] = 9;
			}
		}

		if (isset($data['last_login'])){
			$data['last_login'] = (string) $data['last_login'];
		}

		if (isset($data['pass_change'])) {
			$data['pass_change'] = (string) $data['pass_change'];
		}
		if (isset($data['geidea_terminal_id'])) {
			$data['geidea_terminal_id'] = $data['geidea_terminal_id'];
		}

		try {
			$user =  parent::add($data);
			return $user;//->toArray();
		}
		catch(\Exception $ex){
			Error::trigger("user.add", [$ex->getMessage()]);
			return [];
		}
    }

    function change(array $data, $user_id){
		$user = static::find($user_id);

		$data['first_name'] = cleanNameString($data['first_name']);

		if (!isset($data['first_name']) || $data['first_name'] == '') {
			Error::trigger("user.change", ["Please Enter First name in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

		$data['last_name'] = cleanNameString($data['last_name']);

		if (!isset($data['last_name']) || $data['last_name'] == '') {
			Error::trigger("user.change", ["Please Enter Last Name in English/Arabic. Special Characters are not allowed."]);
			return false;
		}

		if (isset($data['designation'])) {
			$data['designation'] = cleanNameString($data['designation']);

			if (!isset($data['designation']) || $data['designation'] == '') {
				Error::trigger("user.change", ["Please Enter Designation in English/Arabic. Special Characters are not allowed."]);
				return false;
			}
		}

		//check if employee id is unique amoung table or not.
		$userWithEmployeeIdExists = User::where('employee_id', $data['employee_id'])->where('user_id', '!=', $user_id)->first();
		if (is_object($userWithEmployeeIdExists)) {
			//it means user exists with same employee Id.
			Error::trigger("user.change", ["Entered Employee Id is assigned to another user."]);
			return false;
		}

		if (isset($data['email'])) {
			if ($user->email != $data['email']) {
				$user->email = (string) $data['email'];
			} else {
				unset($data['email']);
			}
		} else {
			Error::trigger('user.change', ["Email is required."]);
			return false;
		}

		if (isset($data['gender'])) {
			$user->gender = (string) $data['gender'];
		} else {
			Error::trigger('user.change', ["Gender is required."]);
			return false;
		}

		if (isset($data['group_id'])) {
			$user->group_id = (int) $data['group_id'];
		} else {
			Error::trigger('user.change', ["User Roles are not specified."]);
			return false;
		}

		if (isset($data['rsm_region'])) {
			$user->rsm_region = (int) $data['rsm_region'];
		}

		if (isset($data['reporting_to'])) {
			$user->reporting_to = (int) $data['reporting_to'];
		}

		if (isset($data['mobile']) && $data['mobile'] != '') {
			$user->mobile = (int) $data['mobile'];
		}

		if (isset($data['status'])) {
			$data['status'] = (int) $data['status'];
			if($data['status'] != 1){
				$data['status'] = 9;
			}
		}

		if (isset($data['last_login'])) {
			$user->last_login = (string) $data['last_login'];
		}

		if (isset($data['pass_change'])) {
			$user->password = Hash::make($data['pass_change']);
			$data['password'] = Hash::make($data['pass_change']);
			$data['plain_password'] = $data['pass_change'];
		}

		if (!isset($data['pass_change'])){
			$user->password = $data['old_password'];
		}

		if (isset($data['password'])) {
			//if changed is passed in 'password' field
			$data['plain_password'] = $data['password'];
			$data['password'] = Hash::make($data['password']);
		} else if ($data['old_password']) {
			$data['plain_password'] = $data['old_password'];
			$data['password'] = Hash::make($data['old_password']);
		}

		if (isset($data['url'])) {
			$user->url = (string) $data['url'];
		}

		if (isset($data['geidea_terminal_id'])) {
			$user->geidea_terminal_id = $data['geidea_terminal_id'];
		}

		unset($data['old_password']);
		unset($data['pass_change']);
		unset($data['vehicle_id']);

		//dd($data);
		try {
			//save previous employee id
			$ex_employee_id = $user->employee_id;
			$user = parent::change($data, $user_id);
			
			return $user;
		} catch(Exception $ex) {
			Error::trigger("user.change", [$ex->getMessage()]);
		}
  	}

    public static function getRoles($user_id) {
		$user = User::with('group')->where('user_id', $user_id)->first()->toArray();

		$roles = $user['role_id'];
		$userRoles = '';

		if ($roles == '[]') {
			return '';
		}

		if ($roles == '0') {
			return '';
		}

		return '';
	}

	public static function  getUsersByGroup($group, $company_id, $queryOnly = false) {
		$users = \App\Model\User::join('groups', function($join) use ($group, $company_id) {
            $join->on('users.group_id', '=', 'groups.group_id')
            ->where('groups.status', '=', 1)->where('groups.group_name', $group)->where('groups.company_id', $company_id);
        })->where('users.status', 1)->where('users.company_id', $company_id);
		if ($queryOnly) {
			return $users;
		}
		return $users->get()->toArray();
	}


	static function refreshToken($token){
		$customer = Customer::where('auth_key',$token)->first();
		if($customer){
			$customer->valid_till = date("Y-m-d H:i:s", strtotime(date('Y/m/d H:i:s'))+604800);
			$customer->save();
			return 1;
		}
		else{
			return 0;
		}
	}
}
