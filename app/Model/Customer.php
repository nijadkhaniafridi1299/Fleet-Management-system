<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Customer as Validator;
use App\Validator\Validator as ValidatorValidator;
use DB;
use Auth;
// use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Hash;

class Customer extends Model implements AuthenticatableContract, AuthorizableContract,JWTSubject
{
    use Validator, Authenticatable, Authorizable, HasFactory;
    
  protected $primaryKey = "customer_id";
  protected $table = "customers";
  protected $fillable = ['account_type_id', 'agent_id', 'source', 'status', 'token',
  'name', 'email', 'otp', 'mobile', 'staff_id', 'preferred_language_id', 'group_id', 'channel_id', 'sub_channel_id',
  'preferred_communication_id', 'auth_key', 'last_login', 'login_attempt', 'valid_till', 'credit_limit', 'parent_id',
  'erp_id', 'hide_price','current_balance','branch_address', 'password','user_name','email_verified','otp_delay','force_change_password','tax_id'];
  protected $attributes = ['source' => 1, 'status' => 1, 'token' => '', 'agent_id' => 0,
  'email' => '', 'name' => 'Yaa Customer', 'otp' => '2036', 'mobile' => "0511111111", 'staff_id' => 1, 'preferred_language_id' => "ar",
  'preferred_communication_id' => 1, 'auth_key' => '', 'channel_id'=>1, 'sub_channel_id'=>19, 'group_id'=>1, 'erp_id' => null,'last_login' => Null,
  'login_attempt' => 0,'update_customer_sync' => 0, 'parent_id' => Null, 'account_type_id'=>0, 'credit_limit'=>0, 'hide_price'=>0, 'current_balance'=>0,
  'email_verified'=>0,'force_change_password'=>0,'tax_id'=>null];
  
  protected static $columns = [
    "customer_id" => "Customer Id",
    "created_at" => "Created Date",
    "name" => "Name",
    "customer_group" => "Customer Group",
    "channels_name" => "Channel",
    "email" => "Email",
    "mobile" => "Mobile",
    "profile_name" => "Pricing Profile",
    "status" => "Status"
];
  public $timestamps = true;


  function order(){
    return $this->hasMany('App\Model\Order', 'customer_id')->orderBy('order_id','DESC');
  }

  function get_categories(){
    $categories = \App\Model\Category::whereIn('key',['PICKUP','TRANSFER','CWA','SKIP_COLLECTION'])->pluck('category_id');
    return $categories;
  }

  function active_orders(){
    $categories = Customer::get_categories();
    return $this->hasMany('App\Model\Order', 'customer_id')->whereNotIn('order_status_id',[4,6])->whereIn('category_id',$categories)
                ->select(['order_id','customer_id','order_number','net_weight','unit','site_location','pickup_address_id','estimated_end_date','required_start_date','category_id'])
                ->orderBy('order_id','DESC');
  }

  function active_contracts() {
    return $this->hasMany('App\Model\Contract', 'customer_id')->where('end_date', '>', date('Y-m-d H:i:s'))
                ->select(['contract_id','customer_id','contract_number','contract_type','start_date','end_date'])
                ->orderBy('contract_id','DESC');
  }

  function address(){
    return $this->hasMany('App\Model\Address', 'customer_id')->where('status',1)->orderBy('address_id','DESC');
  }
  function corporate_customer_addresses(){
    $address_type_id = \App\Model\AddressType::where('key','CORPORATE CUSTOMER')->value('address_type_id');
    return $this->hasMany('App\Model\Address', 'customer_id')->where('status',1)->where('type',$address_type_id)->orderBy('address_id','DESC');
  }

  function material(){
    return $this->hasMany('App\Model\Material', 'customer_id','customer_id');
}

  function head_office(){
    return $this->hasMany('App\Model\Address', 'customer_id')->where('status',1)->where('type',1)->orderBy('address_id','DESC');
  }

  function address_5(){
    return $this->hasMany('App\Model\Address', 'customer_id')->where('status',1)->orderBy('address_id','DESC')->limit(5);
  }

  function branchAddress(){
    return $this->hasMany('App\Model\Address', 'mobile', 'mobile')->where('status',1)->orderBy('address_id','DESC');
  }

  function channel(){
    return $this->belongsTo('App\Model\Channel', 'channel_id', 'channel_id');
  }

  function sub_channel(){
    return $this->belongsTo('App\Model\Channel', 'sub_channel_id', 'channel_id');
  }

  function group(){
    return $this->belongsTo('App\Model\CustomerGroup', 'group_id', 'group_id');
  }
  function parent()
  {
    return $this->belongsTo('App\Model\Customer', 'parent_id', 'customer_id');
  }
  function child()
  {
    return $this->hasMany('App\Model\Customer', 'parent_id', 'customer_id');
  }
  function company(){
    return $this->hasOne('App\Model\ExtCompContract', 'customer_id', 'customer_id')->where('status',1);
  }

  function ticket(){
    return $this->hasMany('App\Model\Ticket', 'customer_id','customer_id');
  }

  function pendingTicket(){
    return $this->hasMany('App\Model\Ticket', 'customer_id','customer_id')->where('status',1);
  }

  function scam_report(){
    return $this->hasMany('App\Model\CustomerScam', 'customer_id','customer_id')->orderBy('id','desc');
  }
  
  function trips(){
    return $this->hasManyThrough('App\Model\DeliveryTrip' , 'App\Model\Order', 'customer_id','order_id','customer_id','order_id')->orderBy('delivery_trip_id','desc')->limit(10);
  }

  function order_service_requests(){
    return $this->hasManyThrough('App\Model\OrderServiceRequest' , 'App\Model\Order', 'customer_id','order_id','customer_id','order_id')
                ->select(['order_service_request_id','service_category_id']);
  }

    public static function getTableColumns() {
        return self::$columns;
    }

    function customerGroup() {
        return $this->belongsTo('App\Model\CustomerGroup', 'customer_group_id');
    }

    function pricing_profile(){
      return $this->belongsTo('App\Model\PricingProfile', 'pricing_profile_id');
    }

  function add($data){

    $data['name'] = (string) $data['name'];

    if(!isset($data['account_type_id'])){
      $data['account_type_id'] = 0;
      $data['hide_price'] = 0;
    }
    if(!isset($data['preferred_language_id'])){
      $data['preferred_language_id'] = 1;
    }
    if($data['channel_id'] == Null){
      $data['channel_id'] = 1;
    }
    if(!isset($data['sub_channel_id']) || $data['sub_channel_id'] == Null){
      $data['sub_channel_id'] = 19;
    }
    if(isset($data['group_id']) && $data['group_id'] == Null){
      $data['group_id'] = 1;
    }
    if(!isset($data['agent_id'])){
      $data['agent_id'] = 0;
    }
    if(!isset($data['status'])){
      $data['status'] = 1;
    }
    if(!isset($data['staff_id'])){
      $data['staff_id'] = Null;
    }
    if(!isset($data['token'])){
      $data['token'] = '';
    }
    if(isset($data['password'])){
      $data['password'] = Hash::make($data['password']);
    }
    if(!isset($data['preferred_communication_id'])){
      $data['preferred_communication_id'] = "ar";
    }
    if(!isset($data['otp'])){
      $data['otp'] = 2036;
    }
    if(!isset($data['credit_limit']) || $data['credit_limit'] == Null){
      $data['credit_limit'] = 0;
    }
    if(isset($data['credit_limit']) && $data['credit_limit'] != Null){
      $data['current_balance'] = $data['credit_limit'];
    }

    $date = date('Y/m/d H:i:s');
    $auth = md5("1234567890".$data['mobile'].date('Y/m/d H:i:s'));
    $valid = date("Y-m-d H:i:s", strtotime($date)+604800);
    $data['source'] = (int)$data['source'];
    $data['mobile'] = (string) $data['mobile'];
    $data['auth_key'] = $auth;
    $data['valid_till'] = $valid;

    try{
      $customer = parent::add($data);
      return $customer;
    }
    catch(\PDOException $ex){
      Error::trigger("customer.add", [$ex->getMessage()]) ;
    }
  }

  function change(array $data, $customer_id){

    $customer = static::find($customer_id);

    if(isset($data['account_type_id'])){
      $customer->account_type_id = (int) $data['account_type_id'];
    }
    if(isset($data['parent_id'])){
      $customer->parent_id = (int) $data['parent_id'];
    }
    if(isset($data['preferred_language_id'])){
      $customer->preferred_language_id = (int) $data['preferred_language_id'];
    }
    if(isset($data['channel_id'])){
      $customer->channel_id = (int) $data['channel_id'];
    }
    if(isset($data['sub_channel_id'])){
      $customer->sub_channel_id = (int) $data['sub_channel_id'];
    }
    if(isset($data['group_id'])){
      $customer->group_id = (int) $data['group_id'];
    }
    if(isset($data['agent_id'])){
      $customer->agent_id = (int) $data['agent_id'];
    }
    if(isset($data['status'])){
      $customer->status = (int) $data['status'];
    }
    if(isset($data['staff_id'])){
      $customer->staff_id = (int) $data['staff_id'];
    }
    if(isset($data['token'])){
      $customer->token = (string) $data['token'];
    }
    if(isset($data['preferred_communication_id'])){
      $customer->preferred_communication_id = (int) $data['preferred_communication_id'];
    }
    if(isset($data['preferred_language_id'])){
      $customer->preferred_language_id = $data['preferred_language_id'];
    }
    if(isset($data['email'])){
      $customer->email = (string) $data['email'];
    }
    if(isset($data['erp_id'])){
      $customer->erp_id = (string) $data['erp_id'];
    }
    if(isset($data['credit_limit'])){
      $customer->credit_limit = (string) $data['credit_limit'];
    }
    if(isset($data['branch_address'])){
      $customer->branch_address = (string) $data['branch_address'];
    }
    if(isset($data['password']) && $data['password'] != ''){
      $customer->password = Hash::make($data['password']);
    }
    if(isset($data['user_name'])){
      $customer->user_name = (string) $data['user_name'];
    }
    if(isset($data['tax_id'])){
      $customer->tax_id = $data['tax_id'];
    }
    if(!isset($data['otp'])){
      $data['otp'] = 2036;
    }

    $date = date('Y/m/d H:i:s');
    $auth = md5("1234567890".$data['mobile'].date('Y/m/d H:i:s'));
    $valid = date("Y-m-d H:i:s", strtotime($date)+604800);


    if(isset($data['name'])){
    $customer->name = (string) $data['name'];
    }
    if(isset($data['source'])){
    $customer->source = (int) $data['source'];
    }
    if(isset($data['mobile'])){
    $customer->mobile = (string) $data['mobile'];
    }
    if(isset($data['auth_key'])){
    $customer->auth_key = $auth;
    }
    $customer->valid_till = $valid;
    try{
      $customer->save();
    }
    catch(\Exception $ex){
      Error::trigger("customer.change", [$ex->getMessage()]) ;
    }

  }

  function login(array $data){
    DB::connection()->enableQueryLog();
    $customer = static::where("mobile",$data['mobile'])->where("otp",$data['otp'])->where('status',1)->first();
    $laQuery = DB::getQueryLog();
    if(is_object($customer)){
      $customer->last_login = date("Y-m-d H:i:s");
      $customer->login_attempt = 0;
      $customer->auth_key = md5("1234567890".$customer->mobile.date('Y/m/d H:i:s'));
      $customer->otp_delay = Null;
      $customer->save();

      return $customer->toArray();
    }
    else{
      $customer = static::where("mobile",$data['mobile'])->first();
      if(is_object($customer)){
        $customer->last_login = date("Y-m-d H:i:s");
        $customer->login_attempt++;
        $customer->save();
        return [];
      }
    }
  }

  function checkCustomer($data, $source = 1){

    $customer = static::where("mobile",$data)->first();
    $otp = rand(1000, 9999);

    if(is_object($customer)){

      session(['customerStatus'=>1]);
      //return true;
    }
    else{ //echo "ere";exit;
      $customer = new Customer();
      session(['customerStatus'=>0]);
      $customer->mobile = $data;
      $customer->source = $source;
      $customer->account_type_id = 0;
      $customer->channel_id = 1;
      $customer->sub_channel_id = 19;
      $customer->group_id = 1;
      $customer->credit_limit = 0;
      $customer->hide_price = 0;
      $customer->auth_key = md5(rand(100000000,99999999).$data.date('Y/m/d H:i:s')); // Generate customer token
      $customer->valid_till = date("Y-m-d H:i:s", strtotime(date('Y/m/d H:i:s'))+604800); // Generate valid till for customer token

    }

    if ($data == "966580637124" || $data == "966542983565" || $data == "966590775565"){
      $otp = "4444";
    }

    $customer->otp = $otp;
    $customer->otp_delay = date('Y-m-d H:i:s');

    $code=0;

    try{

      $customer->save();

      if($source != 4){
        if($data != "966580637124" && $data != "966542983565" && $data != "966590775565"){
          $oSMS = new \App\Notification\SMS( [$customer->mobile], __("Please use passcode to login {$otp}") );
          $code = $oSMS->sendPost();
        }
        if(isset($code['errorCode']) && $code['errorCode'] != 'ER-00' && $data != "966580637124" && $data != "966542983565" && $data != "966590775565"){
          Error::trigger("sendotp", $code['message']);
          return false;
        }
      }

      return $customer;

    }
    catch(\PDOException $ex){
      Error::trigger("sendotp", [$ex->getMessage()]);
      return false;
    }
  }

  function logSession($id){
    $currentCustomer = static::where("customer_id", $id)->first();
    if($currentCustomer){
      $customer = $currentCustomer->toArray();
      session(['customer_detail'=>$customer]);
      return session('customer_detail');
    }
    else {

    }
  }

  function edit(array $data, $customer_id){
    $customer = static::find($customer_id);
    $customer->first_name = (string) $data['first_name'] ;
    $customer->last_name = (string) $data['last_name'] ;
    $customer->email = (string) $data['email'] ;
    try{
      $customer->save();
    }
    catch(\PDOException $ex){
      Error::trigger("profile.edit", [$ex->getMessage()]) ;
    }
  }

  static function getLoggedInCustomer(){
    $customer['customer_id'] = Auth::id();
    // session("customer");
    
     return $customer;
  }

  static function isLoggedIn(){
    $info = static::getLoggedInCustomer();
    return (is_array($info) && count($info) > 0);
  }

  static function checkId(array $data){

    $customer = static::where("customer_id",$data['client_id'])->first();
    if(is_object($customer)){
      $customer->name = $data['fullname'];

      try{
        $customer->save();
        return 1;
      }
      catch(\PDOException $ex){
        Error::trigger("sendotp", [$ex->getMessage()]) ;
        return 0;
      }
    }
    else{
      return 0;
    }
  }

  static function checkLogin($mobile, $user_name=''){
    $model = new Mobile();
    if($user_name != ''){
      $customer = static::where('user_name', $user_name)->get()->first();
    }
    else{
      $mobile = $model->isValidMobile($mobile);
      $customer = static::where('mobile', $mobile)->get()->first();
    }
    if($customer){
      if($customer->last_login != Null){
        $date =  Date('Y-m-d H:i:s');
        $start = strtotime($customer->last_login);
        $end = strtotime($date);
        $date_check = (($end-$start)/60);
        if($date_check > 5){
          $customer->login_attempt = 0;
          $customer->last_login = date('Y-m-d H:i:s');;
          $customer->save();
          return true;
        }
        if($date_check < 5 && $customer->login_attempt >= 5){
          return false;
        }
        else{
          return true;
        }
      }
      else{
        return true;
      }
    }
    else{
      return true;
    }
  }

  function updateData($data,$index){
    return parent::batchUpdate($data,$index);
  }

  function checkCustomerStatus($mobile){
    $model = new Mobile();
    $mobile = $model->isValidMobile($mobile);
    $customer = static::where('mobile',$mobile)->first();
    $result = ['customer'=>'no','last_order_status'=>'','last_order_date'=>''];
    if($customer){
      $result['customer'] = 'yes';
      $order = Order::with('order_status_detail:order_status_id,order_status_title')->where('customer_id',$customer->customer_id)->orderBy('order_id','desc')->first();
      if($order){
        $order = $order->toArray();
        try {
          $status = json_decode($order['order_status_detail']['order_status_title'],true);
          $status = $status['en'];
        } catch (\Exception $e) {
          $status = '';
        }
        $result['last_order_status'] = $status;
        $result['last_order_date'] = $order['created_at'];
      }
    }
    return $result;
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

    public function changePassword($data, $customerId) {
        $customer = Customer::find($customerId);
        // dd($customer->password,Hash::make($data['old_password']));
		//dd(Hash::make($data['old_password']));
		if (Hash::check($data['old_password'], $customer->password)) {
     
            if ($data['old_password'] != $data['new_password']) {
                $customer->password = Hash::make($data['new_password']);
                $customer->force_change_password = 0;
                $password_changed = 1;
            } else {
                $message = __('Password cannot be same as old password');
                Error::trigger("customer.change", [$message]);
                // return response()->json([
                //     "customer" => $customer,
                //     "errors" => $message
                // ]);
            }
        } else {
          
            $message = __('Current Password entered is incorrect');
			      Error::trigger("customer.change", $message);
            // return response()->json([
            //     // "customer" => $customer,
            //     "errors" => $message
            // ]);
        }

        try {
            $customer->save();
        } catch(Exception $ex) {
            Error::trigger("customer.change", [$ex->getMessage()]);
        }
    }
}

