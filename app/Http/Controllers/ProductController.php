<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use  App\Model\Product;
use  App\Model\Category;
use  App\Model\Customer;


class ProductController extends Controller

{
    public function index(Request $request)
    {
      $user = auth()->guard('oms')->user();
     
      // $parent_customer_id = getParentCustomer($user->customer_id);
      $customer_id = $user->customer_id;
      $category = Category::select('category_id','parent_id','customer_id','category_name','category_description','category_sort','key')
      ->where('category_status',1);
      
      // if($parent_customer_id != null){
      // $category->where(
      //   function($query) use($customer_id,$parent_customer_id){
      //     return $query
      //     ->where('customer_id',$customer_id)
      //     ->orWhere('customer_id',$parent_customer_id);
      //    });
      // }else{
        $category->where('customer_id',$customer_id);
      // }

  

      $category = $category->get()->toArray();  
      return response()->json([
        "Code" => 200,
        'categories' => $category
      ]);
    }

}
