<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\GeoFence as GeoFence;
use App\Model\Vehicle as Vehicle;
use App\Model\VehicleGroup as VehicleGroup;
use Validator;
use DB;
class GeoFenceController extends Controller

{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getGeoFences(Request $request) {

        $data =  $request->all();
        $data['perPage'] = isset($data['perPage']) && $data['perPage'] != '' ? $data['perPage'] : 10;

        $geofence = GeoFence::with('vehicles:vehicle_id,geofence_id','vehicle_groups:vehicle_group_id,geofence_id')
                    ->withCount([
                        'vehicles as in_zone' => function ($query) {
                            $query->where('zone_in', 1);
                        }])
                    ->withCount([
                            'vehicles as out_of_zone' => function ($query) {
                                $query->where('zone_in', 0);
                            }])->orderBy('created_at','DESC');
        if(isset($data['name']) && $data['name'] != ""){
            $geofence->whereRaw('LOWER(`name`) LIKE ? ',['%'.trim(strtolower($data['name'])).'%']);
        }
        if(isset($data['status']) && $data['status'] != ""){
            $geofence->where('status', $data['status']);
        }
        if(isset($data['type']) && $data['type'] != ""){
            $geofence->where('type', $data['type']);
        }
        if(isset($data['zone_group']) && $data['zone_group'] != ""){
            $geofence->whereRaw('LOWER(`zonegroup`) LIKE ? ',['%'.trim(strtolower($data['zone_group'])).'%']);
        }
        if(isset($data['vehicle_id']) && $data['vehicle_id'] != ""){
            $vehicle_id = $data['vehicle_id'];
            $geofence->whereHas('vehicles', function($query) use($vehicle_id){
              $query->where('vehicle_id', $vehicle_id);  
            });
        }
        if(isset($data['vehicle_group_id']) && $data['vehicle_group_id'] != ""){
            $vehicle_group_id = $data['vehicle_group_id'];
            $geofence->whereHas('vehicle_groups', function($query) use($vehicle_group_id){
              $query->where('vehicle_group_id', $vehicle_group_id);  
            });
        }
        $geofence = $geofence->paginate($data['perPage']);
        
        // $count = Geofence::withCount('vehicles')->having('zone_in', '=', 1)->get();
        // return $count;
        $vehicles = Vehicle::orderBy('created_at','DESC')->get(['vehicle_id','vehicle_type_id','vehicle_code','vehicle_plate_number'])->toArray();
        $vehicle_groups = \App\Model\VehicleGroup::orderBy('created_at','DESC')->get(['vehicle_group_id','title'])->toArray();
   
       $vehicles_list = $vehicle_groups_list = [];
       
        for($i=0;$i<count($geofence);$i++)
            {

                $geofence[$i]['coordinates']=json_decode($geofence[$i]['coordinates']);
                foreach($geofence[$i]['vehicles'] as &$veh){
                    array_push($vehicles_list,$veh['vehicle_id']);
                }
                $geofence[$i]['vehicles'] = $vehicles_list;

                foreach($geofence[$i]['vehicle_groups'] as &$veh_group){
                    array_push($vehicle_groups_list,$veh_group['vehicle_group_id']);
                }
                $geofence[$i]['vehicle_groups'] = $vehicle_groups_list;
                $vehicles_list = $vehicle_groups_list = [];


            }

          

            return response()->json([

                "code" => 200,
                "data" => $geofence,
                "vehicles" => $vehicles,
                "vehicle_groups" => $vehicle_groups
                ,
                 "message" => 'Geo Fence Loaded',
              
             ]);

        // }
        // else 
        // {
        //     return response()->json([

        //         "code" => 204,
        //         "data" => [
        //             "data" => []
        //         ],
        //          "message" => 'No Geo Fence Found',
              
        //      ]);
        // }
    
    }



    public function create(Request $request) {
     
        $data = json_decode($request->getContent(),true);

        // $rules = [
        //     'name' => 'nullable',
        //     'zonegroup' => 'nullable|min:1',
        //     'type' => 'required|string|min:1',
        //     'color' => 'required|string|min:1',
        //     'radius' => 'required|min:1',
        //     'switch' => 'required|boolean',
        //     'coordinates' => 'array',
        //     'vehicle_ids' => 'array|exists:vehicles,vehicle_id',
        //     'vehicle_group_id' => $data['vehicle_group_id']
        // ];

        $validator = Validator::make([
            'name' => $data['name'],
            'zonegroup' => $data['zonegroup'],
            'type' => $data['type'],
            'color' => $data['color'],
            'radius' => $data['radius'],
            'switch' => $data['switch'],
            'coordinates' => $data['coordinates'],
            'vehicle_ids' => $data['vehicle_ids'],
            'vehicle_group_id' => $data['vehicle_group_id']
        
        ],[
            'name' => 'nullable',
            'zonegroup' => 'nullable',
            'type' => 'required|string|min:1',
            'color' => 'required|string|min:1',
            'radius' => 'nullable',
            'switch' => 'boolean',
            'coordinates' => 'array',
            'vehicle_ids' => 'array|exists:vehicles,vehicle_id',
            'vehicle_group_id' => 'nullable|array|exists:vehicle_groups,vehicle_group_id'
        ]);
      
        //$validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        // $addGeoFence[] = [
                                
        //     'name' => $data['name'],
        //     'zonegroup' =>  $data['zonegroup'],
        //     'color' => $data['color'],
        //     'type' => $data['type'],
        //     'radius' => $data['radius'],
        //     'switch' => $data['switch'],
        //     'coordinates' =>  json_encode($data['coordinates']),
        //     'created_at' => date('Y-m-d H:i:s') 
        // ];
       
       
        //$insertGeoFence=GeoFence::insert($addGeoFence);
        $geofence = new GeoFence();
        $geofence = $geofence->add($data);

        
        $errors = \App\Message\Error::get('geofence.add');

        if (isset($errors) && count($errors) > 0) {
            return response()->json([
                "code" => 400,
                "errors" => $errors
            ]);
        }

        if (is_object($geofence)) {
            if (count($data['vehicle_ids']) > 0) {

                try {
                    Vehicle::whereIn('vehicle_id', $data['vehicle_ids'])->update(['geofence_id'=>$geofence->id]);
                } catch(Exception $ex) {
                    return response()->json([
                        "code" => 400,
                        "message" => $ex->getMessage()
                    ]);
                }   
                
            }

            if (count($data['vehicle_group_id']) > 0) {
                try {
                    VehicleGroup::whereIn('vehicle_group_id', $data['vehicle_group_id'])->update(['geofence_id'=>$geofence->id]);
                } catch(Exception $ex) {
                    return response()->json([
                        "code" => 400,
                        "message" => $ex->getMessage()
                    ]);
                }  
            }
            // for($i=0;$i<count($data['vehicle_ids']);$i++)
            //     {
            //         $updateVehicleGF=Vehicle::where('vehicle_id',$data['vehicle_ids'][$i])
            //         ->update(['geofence_id'=>$getGFid]);
                

            //     }
            // }
        }
        // $getGFid=GeoFence::orderBy('id', 'desc')->withTrashed()->pluck('id')->take(1)->toArray();
        // $getGFid=$getGFid[0];
    
        
        return response()->json([
            "code" => 200,
            "message" => "GeoFence Successfully Added"
        ]);
        
    }

    public function change(Request $request,$geofenceid) {
     
        $data = json_decode($request->getContent(),true);


        $validator = Validator::make([
            'id' => $geofenceid,
            'name' => $data['name'],
            'zonegroup' => $data['zonegroup'],
            'type' => $data['type'],
            'radius' => $data['radius'],
            'switch' => $data['switch'],
            'color' => $data['color'],
            'coordinates' => $data['coordinates'],
            'vehicle_ids' => $data['vehicle_ids'],
            'vehicle_group_id' => $data['vehicle_group_id']
        
        ],[
            
            'id' => 'required|int|exists:fm_geofences,id',
            'name' => 'nullable',
            'zonegroup' => 'nullable',
            'type' => 'nullable',
            'radius' => 'nullable',
            'color' => 'nullable',
            'switch' => 'boolean',
            'coordinates' => 'array',
            'vehicle_ids' => 'array|exists:vehicles,vehicle_id',
            'vehicle_group_id' => 'nullable|array|exists:vehicle_groups,vehicle_group_id'
        ]);
 

        if ($validator->fails()) {
    
            return responseValidationError('Fields Validation Failed.', $validator->errors());
        }

        // if(isset($data['vehicle_group_id']) && $data['vehicle_group_id'] != null){
        //    $data['vehicle_ids'] = \App\Model\VehiclesInVehicleGroup::whereIn('vehicle_group_id', $data['vehicle_group_id'])->pluck('vehicle_id');
        // }



        $updateGeoFence = [               
            'name' => $data['name'],
            'zonegroup' =>  $data['zonegroup'],
            'type' => $data['type'],
            'color' => $data['color'],
            'radius' => $data['radius'],
            'switch' => $data['switch'],
            'coordinates' =>  json_encode($data['coordinates']),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
 
   
        $addGeoFence=GeoFence::where(['id'=> $geofenceid])->update($updateGeoFence);

       try {
            Vehicle::where('geofence_id', $geofenceid)->update(['geofence_id'=>null]);
            Vehicle::whereIn('vehicle_id', $data['vehicle_ids'])->update(['geofence_id'=>$geofenceid]);
        } catch(Exception $ex) {
            return response()->json([
                "code" => 500,
                "message" => $ex->getMessage()
            ]);
        }
            
        // for ($i=0; $i<count($data['vehicle_ids']); $i++)
        // {
        //     $updateVehicleGF=Vehicle::where('vehicle_id',$data['vehicle_ids'][$i])->update(['geofence_id'=>$geofenceid]);
        // }

        try {
            VehicleGroup::where('geofence_id', $geofenceid)->update(['geofence_id'=>null]);
            VehicleGroup::whereIn('vehicle_group_id', $data['vehicle_group_id'])->update(['geofence_id'=>$geofenceid]);
        } catch(Exception $ex) {
            return response()->json([
                "code" => 500,
                "message" => $ex->getMessage()
            ]);
        }

        return response()->json([
            "code" => 200,
            "message" => "GeoFence Successfully Updated"
        ]);
    }

    public function remove(Request $request, $geofenceid) {
     
        //$data = json_decode($request->getContent(),true);

        $validator = Validator::make([    
            'id' => $geofenceid
        ],[
            'id' => 'required|int|exists:fm_geofences,id'
        ]);

        if ($validator->fails()){
    
            return responseValidationError('Fields Validation Failed.', $validator->errors());

        }

        //$geofenceid=$data['id'];
    
        $addGeoFence=GeoFence::where('id',$geofenceid)->delete();

        return response()->json([
            "code" => 200,
            "message" => "GeoFence Successfully Deleted"
        ]);
    } 

    public function checkGeoFence(Request $request) 
    
    {
      $getvehicles=Vehicle::whereNotNull('geofence_id')->get('vehicle_id')->toArray();


        $vertices_x = array(25.251686934355526, 25.435372906442552, 25.057770881218403, 24.639063984652633);    // x-coordinates of the vertices of the polygon
        $vertices_y = array(45.71960284882813,46.12609698945313,46.60949542695313,45.5218489425); // y-coordinates of the vertices of the polygon
        $points_polygon = count($vertices_x) - 1;  // number vertices - zero-based array
        $longitude_x = 25.066667;  // x-coordinate of the point to test
        $latitude_y = 45.450568;    // y-coordinate of the point to test
        
        if (is_in_polygon($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)){
         { 
            return response()->json([
                "code" => 200,
                "message" => "Vehicle Is Inside The Zone"
            ]);
         }
        }
        else 
        {
            return response()->json([
                "code" => 200,
                "message" => "Vehicle Is Outside The Zone"
            ]);
        }

        
    } 

}