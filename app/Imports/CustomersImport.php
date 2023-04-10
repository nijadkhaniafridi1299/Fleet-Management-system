<?php

namespace App\Imports;

use DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CustomersImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        //
        $data = [];

        foreach ($rows as $row) 
        {
            $data[] = array(
                    'action_detail' => $row[0],
                    'status'        => $row[1]
                );
        }

        DB::table('actions')->insert($data);
    }
}
