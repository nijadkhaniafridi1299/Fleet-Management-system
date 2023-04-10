<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\CtSettings as CtSettings;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Model\StoreConstraints as StoreConstraints;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Model\Store as Store;
use Validator;
use DB;





class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */


    public function getStoresAction(Request $request){

   
        $user=Auth::user();
        $store_id=$user['default_store_id'];

      

       
    
            
    
        $stores = Store::where('store_id', $store_id)
        ->where('status',1)->get()
        ->toArray();
        



        $dataArray = array();
        
        if(count($stores)>0){
            foreach ($stores as $key => $value) {
                $tmpData['store_id'] = $value['store_id'];
                $tmpData['store_code'] = $value['erp_id'];
                $tmpData['store_name'] = json_decode($value['store_name'],true);
                $tmpData['loacation'] = json_decode($value['map_info'],true);
               
                array_push($dataArray, $tmpData);
            }
       
        }
        else{
        
                return response()->json([
                    'code' => 404,
                    'data' => '',
                    "message" => __("No stores Loaded!"),
                ]);
        }

        return response()->json([
            'code' => 200,
            'data' => array("Warehouses" => $dataArray),
            "message" => __("Stores Loaded!"),
        ]);



    }
    public function GetStoreConstraints(Request $request,$store_id){
       $user=Auth::user();
        $storeConstraints = CtSettings::where('store_id',$store_id)->get()->toArray();
 


        for($i=0;$i<count($storeConstraints);$i++)
        {
            $data[] =[
            "id" =>$storeConstraints[$i]['id'],
            "settingName"=> $storeConstraints[$i]['settingName'],
            "desc"=> $storeConstraints[$i]['desc'],
            "inputConstraint"=> [
                "inputType"=> $storeConstraints[$i]['inputType'],
                "valuesConstraint"=> json_decode($storeConstraints[$i]['valuesConstraint']),
                 "currentValue"=> $storeConstraints[$i]['currentValue'],
            ],

            ];

        }

     
        

        if(!empty($storeConstraints)){
            //getConstraints

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $data,
                "message" => __("Constraints Loaded!"),
            ]);
        }else{
            return response()->json([
                'code' => 404,
                'data' => $data,
                "message" => __("No Constraints Loaded!"),
            ]);
        }
    }
 
    public function updateStoreConstraintsAction(Request $request,$store_id){

     $user = Auth::user();

     $data =  json_decode($request->getContent(),true);


    for($i=0;$i<count($data['data']);$i++)
{
 $storeConstraints = CtSettings::where('store_id',$store_id)
 ->where('id',$data['data'][$i]['id'])
 ->update(['currentValue'=> $data['data'][$i]['inputConstraint']['currentValue']]);



    }

         return response()->json([
             'code' => Response::HTTP_OK,
             "message" => __("Store Constraints Updated Successfully!"),
         ]);
 
 }



    public function index()
    {
        return view('store::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('store::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('store::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('store::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
