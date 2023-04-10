<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;
use Validator;
use App\Model\Address;
use App\Model\Location;
use App\Model\Customer;
use App\Model\Store;
use App\Model\AddressType;
use App\Model\CustomerWarehouse;
use App\Model\CompanyWarehouse;
use App\Model\Order;
use App\Model\Skip;
use App\Model\SapApi;
use Illuminate\Database\Query\Builder;

class AddressController extends Controller
{
    public function getAddresses(Request $request) {

        $data =  json_decode($request->getContent(),true);
        
        if ($data == "") {
            return response()->json([
				"Code" => 403,
				"message" => "invalid input"
            ]);
        }
        
		if(!isset($data['customer_id'])){
			return response()->json([
				"Code" => 403,
				"message" => "Customer ID is missing"
			]);
		}
		$user = auth()->guard('oms')->user();
		if($user->customer_id != $data['customer_id']){
			return response()->json([
				"Code" => 403,
				"Message" => "Unauthorized User."
			]);
		}
		$addresses = Address::where('status',1)->where('customer_id',$data['customer_id'])->orderBy('created_at','DESC')->get();
		$address_types = AddressType::get();
		$cities = Location::select('location_id', 'location_name')->where('location_level_id', 1)->where('status', 1)->get()->toArray();
    $cwa_addresses = Address::where('status',1)->where('customer_id',$data['customer_id'])->whereHas('type', function($q){
      $q->where('key', '=', 'CORPORATE CUSTOMER');
  })->orderBy('created_at','DESC')->get();
  

		return response()->json([
			"Code" => 200,
			"data" => [
				"addresses" => $addresses,
				"address_types" => $address_types,
				"cwa_addresses" => $cwa_addresses,
				"cities" => $cities
			]
		]);
    }

    public function addUpdateAQGAddress(Request $request){

      $errors = [];
        $data = json_decode($request->getContent(),true);
        $data = $request->all(); $message = "Address added Successfully.";
        $address = '';
        $validator = Validator::make($data, [
            'address_title'=>'required',
            'longitude'=>'required',
            'latitude'=>'required',
            'status'=>'required'
        ]);
           
        if ($validator-> fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }
      // $map_info = array("latitude" => $data['latitude'],"longitude" => $data['longitude']);
      // $map_info = json_encode($map_info);
      // dd($map_info);
        $address_id = null;
        // dd($data['longitude'], $data['latitude']);
      
        if (isset($data['store_id']) && $data['store_id'] != null && $data['store_id'] != ""){
          // DB::enableQueryLog();
            $address_id = $data['store_id'];
           $all_data = [
          'address' => $data['address'],
          // 'type' => isset($data['type']) ? $data['type'] : 0,
          'store_name' => isset($data['address_title']) ? $data['address_title'] : null,
          // "map_info" => $map_info,
          'longitude' => isset($data['longitude']) ? $data['longitude'] : 0 ,
          'latitude' => isset($data['latitude']) ? $data['latitude'] : 0 ,
          // 'customer_id' => isset($data['customer_id']) ? $data['customer_id'] : 0,
          'location_id' => isset($data['location_id']) ? $data['location_id'] : 0,
          'status' => $data['staus'] , 'created_at' => date("Y-m-d H:i:s"),
          'created_at' => date("Y-m-d H:i:s"),
          'updated_at' => date("Y-m-d H:i:s")
        ];
      
        $store = new Store();
    
        $store = $store->change($all_data,$address_id);
          if (!is_object($store)) {
              array_push($errors, \App\Message\Error::get('store.change'));
          }
          if (count($errors) > 0) {
              return respondWithError($errors,0,500);
          }
          $message = "Data Updated Successfully";
       }
        else{
              
                  if(!isset($data['address']) || $data['address'] == "" || $data['address'] == null){
                        
                          
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
                  }
                  else{
                    $address = $data['address'];
                  }
                  $address_id = Store::insertGetId([
                    'address' => $address,
                    // 'type' => isset($data['type']) ? $data['type'] : 0,
                    'store_name' => isset($data['address_title']) ? $data['address_title'] : null,
                    // "map_info" => $map_info,
                    'longitude' => isset($data['longitude']) ? $data['longitude'] : 0 ,
                    'latitude' => isset($data['latitude']) ? $data['latitude'] : 0 ,
                    // 'customer_id' => isset($data['customer_id']) ? $data['customer_id'] : 0,
                    'location_id' => isset($data['location_id']) ? $data['location_id'] : 0,
                    'status' => $data['status'] , 'created_at' => date("Y-m-d H:i:s"),
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                  ]);      
        }


      return response()->json([
        "Code" => 200,
        "data" =>["address_id" => $address_id],
        "Message" => $message 
        ]);  
    }
    public function getAQGAddress(Request $request){
           $data = $request->all();
          //  DB::enableQueryLog();
            $aqdAddres = Store::select("store_id", "store_name", "address", "longitude", "latitude",
                "status",  "erp_id");
        if (isset($data['address']) && $data['address'] !=null) {
            $store_name = $data['address'];
            $aqdAddres->where('store_name', 'LIKE', "%".$store_name."%");

        }
        if(isset($data['status']) && $data['status'] !=null){
        $aqdAddres->where('status', $data['status']);
        }
        $aqdAddres = $aqdAddres->orderBy('store_id', 'DESC')->get()->toArray();

          //  dd(DB::getQueryLog());
        return \response()->json([
            'code'=>200,
             'data'=>[
                 'aqgAddress'=>$aqdAddres,
             ],
            'Message'=>"Data Fetch Successfully"
        ]);
    }
    public function addNewAddress(Request $request){

		$data = json_decode($request->getContent(),true);
		$data = $request->all();
    $message = "Address Added Successfully";
    $address = '';
		if(isset($data['address_id']) && $data['address_id'] != null) {
         $address = Address::find($data['address_id']);
        
        if(($data['address'] == "" || $data['address'] == null) && ($data['latitude'] != $address->latitude || $data['longitude'] !=  $address->longitude))
        {
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
          // dd($address);
        }
        else{
          $address = $data['address'];
          // dd($address);
        }
			  Address::updateOrCreate(['address_id' => $data['address_id']],[ 'address_detail' => isset($data['address_detail']) ? $data['address_detail'] : null,'address' => $address, 'type' => isset($data['type']) ? $data['type'] : 0,'address_title' => isset($data['address_title']) ? $data['address_title'] : null,
				'latitude' => isset($data['latitude']) ? $data['latitude'] : 0, 'longitude' => isset($data['longitude']) ? $data['longitude'] : 0 , 'customer_id' => $data['customer_id'], 'location_id' => 0,'status' => $data['status'] , 'created_at' => date("Y-m-d H:i:s"),  'updated_at' => date("Y-m-d H:i:s")]);  
		     $message = "Address Updated Successfully";  
    }
    else{
        if($data['address'] == "" || $data['address'] == null){
                          
                            
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
          
        }
        else{
          $address = $data['address'];
        }
      	// echo $data['location_id'];
        Address::insert([
          'address' => $address, 
          'type' => isset($data['type']) ? $data['type'] : 0,
          'address_title' => isset($data['address_title']) ? $data['address_title'] : null,
          // 'title' => isset($data['address_title']) ? $data['address_title'] : null,
          'location_id' => isset($data['location_id']) ? $data['location_id'] : 0,
          'latitude' => isset($data['latitude']) ? $data['latitude'] : 0,
          'longitude' => isset($data['longitude']) ? $data['longitude'] : 0 , 
          'customer_id' => $data['customer_id'],
          'status' => $data['status'] , 'created_at' => date("Y-m-d H:i:s"), 
          'updated_at' => date("Y-m-d H:i:s")
        ]); 
    } 
		$addresses = Address::where('status',1)->orderBy('created_at','DESC')->get();
		$address_types = AddressType::get();
		return response()->json([
			"Code" => 200,
			"data" =>["addresses" => $addresses,
			"address_types" => $address_types],
			"Message" =>   $message
		]);
    }

	public function create(Request $request) {
		$errors = [];
		$data = json_decode($request->getContent(),true);

		if (!isset($data['address_title']) || ($data['address_title'] == "")) {
			return response()->json([
				"Code" => 403,
				"Message" => "Missing Address Title."
			]);
		}

		if (!isset($data['address']) || ($data['address'] == "")) {
			return response()->json([
				"Code" => 403,
				"Message" => "Missing Address."
			]);
		}

		if (!isset($data['latitude']) || ($data['latitude'] == "")) {
			return response()->json([
				"Code" => 403,
				"Message" => "Missing Latitude."
			]);
		}

		if (!isset($data['longitude']) || ($data['longitude'] == "")) {
			return response()->json([
				"Code" => 403,
				"Message" => "Missing Longitude."
			]);
		}

		$address = new Address();
		$data['title'] = isset($data['address_title']) ? $data['address_title'] : null;
        //$address = $address->add($data);

		$errors = \App\Message\Error::get('address.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "Code" => 400,
                "errors" => $errors
            ]);
        }

		$addresses = Address::where('status',1)->orderBy('created_at','DESC')->get()->toArray();

        return response()->json([
            "Code" => 200,
            "address" => $address,
            "addresses" => $addresses,
            "message" => 'New Address has been created.',
        ]);
	}  

  public function customerSitesData(Request $request){

    $data =  $request->all();

    $validator = Validator::make($request->all(), [
      'customer_id' => 'required|exists:customers,customer_id'
    ]);

    if ($validator-> fails()) {
        return responseValidationError('Fields Validation Failed.', $validator->errors());
    }
    $customer = Customer::select('customer_id','name','mobile','email','status')
                ->with(['address:address_title,address,customer_id,site_manager_name,site_manager_email,site_manager_mobile',
                'active_orders.order_material.material','head_office:address_id,customer_id,address_title,latitude,longitude,address',
                'active_orders.site_location:address_id,address_title,latitude,longitude',
                'active_orders.category:category_id,category_name,key','active_orders.weight_unit:id,unit',
                'active_orders.pickup:address_id,address_title,latitude,longitude',
                'active_contracts.contract_type:id,contract_type_title',
                'trips:delivery_trip_id,trip_code','trips.pickup_material.material_unit','trips.pickup_material.material',
                'order_service_requests.service_category:service_category_id,title,key','active_orders' => function($query){
                  $query->withCount(['luggers','iot']);
                }])
                ->where('customer_id',$data['customer_id'])
                ->get()->toArray();


    $customer = $customer[0];
    foreach($customer['active_orders'] as &$each_order){
      $each_order['unit'] = isset($each_order['weight_unit']['unit']) ? $each_order['weight_unit']['unit'] : null;
    }

    $material = [];
    // $matches = Order::join('delivery_trips','orders.order_id','=','delivery_trips.order_id')
    //             ->join('pickup_materials','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')
    //             ->join('material','pickup_materials.material_id','=','material.material_id')
    //             ->join('units','pickup_materials.unit','=','units.id')
    //             ->with('pickup:address_id,address_title')
    //             ->select('orders.pickup_address_id','pickup_materials.material_id','material.name as material_name','pickup_materials.weight','pickup_materials.unit','units.unit')
    //             ->where('orders.customer_id',$data['customer_id'])
    //             ->get()->take(10);
                

    // $grouped = $matches->mapToGroups(function ($item, $key) {
    //   return [$item['pickup_address_id'] => $item];
    // });
    
    // if(isset($grouped) && count($grouped) > 0){
    //   foreach($grouped as $key => $group){
      
    //     $grouped_after[$key] = $group->mapToGroups(function ($item, $key) {
    //       return [$item['material_id'] => $item];
    //     });
  
    //   }
    //   $weight = 0;
      
    //   foreach($grouped_after as $key => $value){
    //     foreach($value as $key1 => &$value1){
    //       foreach($value1 as $key2 => &$value2){
    //         $weight += $value2['weight'];
    //         $address = $value2['pickup']['address_title'];
    //         $material = $value2['material_name'];
    //         $unit = $value2['unit'];
    //         unset($value1[$key2]);
    //       }
    //       $value1['sum_weight'] = $weight;
    //       $value1['pickup_address'] = $address;
    //       $value1['material_name'] = $material;
    //       $value1['unit'] = $unit;
    //       $weight = 0;
    //     }
    //   }
    // }
   

    $activity = Order::join('delivery_trips','orders.order_id','=','delivery_trips.order_id')
                ->leftJoin('pickup_materials','delivery_trips.delivery_trip_id','=','pickup_materials.trip_id')
                ->leftJoin('dropoff_materials','delivery_trips.delivery_trip_id','=','dropoff_materials.trip_id')
                ->leftJoin('trip_logs','delivery_trips.delivery_trip_id','=','trip_logs.trip_id')
                ->join('categories','categories.category_id','=','orders.category_id')
                ->join('material','pickup_materials.material_id','=','material.material_id')
                ->join('units','pickup_materials.unit','=','units.id')
                ->with('pickup:address_id,address_title')
                ->select('orders.pickup_address_id','delivery_trips.delivery_trip_id','units.unit','material.name as material_name','categories.category_name',
                'delivery_trips.trip_code','pickup_materials.weight','pickup_materials.material_id','pickup_materials.created_at as pickup_date',
                'dropoff_materials.created_at as unload_date','trip_logs.created_at as trip_updated_at','trip_logs.trip_status_id')
                ->where('orders.customer_id', $data['customer_id'])
                ->where('categories.key', ['PICKUP','TRANSFER','CWA','SKIP_COLLECTION'])
                ->orderBy('pickup_materials.created_at','DESC')
                ->orderBy('trip_logs.created_at','DESC')
                ->distinct()
                ->get()->take(10);
          

                foreach($activity as &$value){
                  if($value['trip_status_id'] == 1){
                    $value['trip_activity'] = "ASSIGNED";
                  }
                  elseif(isset($value['pickup_date']) && $value['pickup_date'] != null && $value['trip_status_id'] == 2){
                    $value['trip_activity'] = "PICKUP";
                  }
                  elseif($value['trip_status_id'] == 2){
                    $value['trip_activity'] = "STARTED";
                  }
                  elseif(isset($value['unload_date']) && $value['unload_date'] != null && $value['trip_status_id'] == 4){
                    $value['trip_activity'] = "DROPOFF";
                  }
                 
                }

                // if()
                $grouped = $activity->mapToGroups(function ($item, $key) {
                  return [$item['pickup_address_id'] => $item];
                });
                
                if(isset($grouped) && count($grouped) > 0){
                  foreach($grouped as $key => $group){
                  
                    $grouped_after[$key] = $group->mapToGroups(function ($item, $key) {
                      return [$item['material_id'] => $item];
                    });
              
                  }
                  $weight = 0;

                  
                  foreach($grouped_after as $key => $value){
                    foreach($value as $key1 => &$value1){
                      foreach($value1 as $key2 => &$value2){
                        if($value2['trip_activity'] == "PICKUP"){
                          $weight += $value2['weight'];
                          $address = $value2['pickup']['address_title'];
                          $material = $value2['material_name'];
                          $unit = $value2['unit'];
                        }
                        unset($value1[$key2]);
                      }
                      $value1['sum_weight'] = $weight;
                      $value1['pickup_address'] = $address;
                      $value1['material_name'] = $material;
                      $value1['unit'] = $unit;
                      $weight = 0;
                    }
                  }
                }


                // return $activity;
  

    $skips = Skip::with('address', 'customer', 'current_skip_level');
        
    if (isset($data['customer_id']) && $data['customer_id'] > 0) {
        $skips->where('customer_id', $data['customer_id']);
    }

    if (isset($data['skip_level_id']) && $data['skip_level_id'] > 0) {
        $skips->where('current_skip_level_id', $data['skip_level_id']);
    }

    if (isset($data['from']) && $data['from'] != '' && isset($data['to']) && $data['to'] != '' ) {
        $from = $data['from'] . ' 00:00:00';
        $to = $data['to'] . ' 23:59:59';
        $skips->whereBetween('created_at', [$from, $to]);
    }

    $skips = $skips->get()->toArray();

    //send filter criteria
    $skip_levels = \App\Model\SkipLevel::withCount([
      'skips' => function ($q) use($data) {
          $q->where('customer_id', $data['customer_id']);
      }
  ])
    ->get()->toArray();



    return response()->json([
      "Code" => 200,
      "data" => [
        "customer" => $customer,
        "material_data" => isset($grouped_after) ? $grouped_after : [],
        "activity" => isset($activity) ? $activity : [],
        "skips" => isset($skips) ? $skips : [],
        "skip_levels" => isset($skip_levels) ? $skip_levels : []
    ],
      "message" => 'Data fetched Successfully.'

    ]);
    

  }
}