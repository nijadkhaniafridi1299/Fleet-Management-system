<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Model\ServiceCategory ;
use App\Model\Material ;
use Auth;



class ServiceCategoryController extends Controller
{
    public function Assetslist(Request $request){

        $parent_id = ServiceCategory::where('key', 'like', '%' . "Assets" . '%')->value('service_category_id');
        $assets_list = ServiceCategory::where('parent_id', $parent_id)->get()->toArray();
        $user = auth()->guard('oms')->user();
        $customer_id = ($user->customer_id);  
        $material_list = Material::where('customer_id',$customer_id)->get(['material_id','parent_id','customer_id','material_code','name','default_unit','status'])->toArray();
        return response()->json([
            "code" => 200,
            "data" => [
              "assets_list" => $assets_list,
              "material_list" => $material_list
            ],
            "message" => "List fetched successfully."
            ]);
    } 

    public function inventoryList(){
     
      $asset_id = ServiceCategory::where('key', 'like', '%' . "Assets" . '%')->value('service_category_id');
      $sub_categories = ServiceCategory::with('items')->whereNotNull('parent_id')->where('parent_id','<>',$asset_id)
                        ->get()->toArray();
      
        return response()->json([
          "code" => 200,
          "data" => [
            "sub_categories" => $sub_categories
          ],
      "message" => "Data fetched Successfully"
      ]);
      }

}