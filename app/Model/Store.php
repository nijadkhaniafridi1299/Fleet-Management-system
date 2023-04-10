<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Store as Validator;
use DB;

class Store extends Model{

    use Validator;

    protected $primaryKey = "store_id";
    protected $table = "stores";
    protected $fillable = [
        'company_id',
        'store_name',
        'managers',
        'address',
        'location_id',
        'store_address_id',
        'parent_id',
        'store_type_id',
        'status',
        'store_meta',
        'created_by',
        'capacity',
        'stock',
        'min_stock_limit',
        'refill_stock_limit'

    ];

    



    public static function getTableColumns() {
        return self::$columns;
    }
    function company(){
        return $this->belongsTo('App\Model\Company', 'company_id');
    }

    function store_type(){
        return $this->belongsTo('App\Model\StoreType', 'store_type_id');
    }

    function manager(){
        return $this->belongsTo('App\User', 'store_manager_id', 'user_id');
    }

    function address(){
        return $this->belongsTo('App\Model\Address', 'store_address_id', 'address_id');
    }

    function vehicles(){
        return $this->hasMany('App\Model\Vehicle', 'store_id');
    }
    function asset_inventory() {
        return $this->hasOne('App\Model\AssetInventory', 'assignee_id', 'store_id');                                      
    }

    function add($data){
        try{
            $store = parent::add($data);
            return $store;
        }
        catch(\Exception $ex){
            Error::trigger("store.add", [$ex->getMessage()]) ;
        }
    }

    function change(array $data, $address_id){
        $address = "";
        $store = Store::find($address_id);   
        if(($data['address'] == "" || $data['address'] == null) && ($data['latitude'] != $store->latitude || $data['longitude'] != $store->longitude)){
           
            $headers = [];
          
            $method = "GET"; $body = array(); $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$data['latitude'] .',' .$data['longitude'] .'&key=AIzaSyBhmQxrhNrV4xOVHN3u41X2qGDMXrxw0II';
            $response = callExternalAPI($method,$url,$body,$headers); 
            $sap_data = [
              'request' => $url,
              'response' => $response,
            ];
            $sap_obj = new SapApi();
            $sap_api = $sap_obj->add($sap_data);
            $response = JSON_DECODE(JSON_DECODE($response,true),true);
            
            $address = $response['results'][0]['formatted_address'];
            $data['address'] = $address;
         
        }
        try{
            return parent::change($data, $address_id);
        }
        catch(Exception $ex){
            Error::trigger("store.change", [$ex->getMessage()]) ;
        }
    }




    public static function getUserStoresIdsFromStores($company_id){
        
        
        $cId = $company_id;
     
        $stores = DB::table('stores')
       ->where("company_id", $cId)
        ->where('store_id','!=',null)->pluck('store_id')
        ->toArray();
   
        if(count($stores) > 0){
            return $stores;
        }
         return false;
    }

}
