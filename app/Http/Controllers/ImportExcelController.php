<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CustomersImport;

class ImportExcelController extends Controller
{
    // function index()
    // {
    //  $data = DB::table('tbl_customer')->orderBy('CustomerID', 'DESC')->get();
    //  return view('import_excel', compact('data'));
    // }

    function import(Request $request)
    {
     $this->validate($request, [
      'select_file'  => 'required|mimes:xls,xlsx'
     ]);

     $path = $request->file('select_file')->getRealPath();

     $data = Excel::import(new CustomersImport,$path);

    //  if($data->count() > 0)
    //  {
    //   foreach($data->toArray() as $key => $value)
    //   {
    //    foreach($value as $row)
    //    {
    //     $insert_data[] = array(
    //      'action_detail'   => $row['action_detail'],
    //      'status'   => $row['status']
    //     );
    //    }
    //   }
    //   if(!empty($insert_data))
    //   {
    //    DB::table('actions')->insert($insert_data);
    //   }
    //  }
     return 'success';
    }
}