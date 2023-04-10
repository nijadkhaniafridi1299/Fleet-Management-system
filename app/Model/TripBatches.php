<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use DB;

class TripBatches extends Model
{

    protected $primaryKey = "batch_id";
    protected $table = "delivery_trip_batches";
    protected $fillable = [
        'batch_no',
        'total_orders',
        'dropped_orders',
        'no_of_vehicles',
        'delivery_date',
        'no_of_trips',
        'plan_date',
        'created_by',
        'is_created',
        'store_id',
        'sal_off_id',
        'execution_date',
        'batch_type',
        'batch_cost',
        'batch_gas_cost',
        'no_of_solutions'
        
    ];
    public $timestamps = true;
  
   
    function batch_user(){
        return $this->belongsTo('App\Model\User', 'created_by', 'user_id');
    }
    public static function getNextBatchtId($paramString,$store_id,$user_id)
    {

       
         $current_id = \DB::select("select batch_id from delivery_trip_batches order by batch_id DESC limit 1");    
     
         if(empty($current_id))
         
         {
            $current_id=new TripBatches();
            $converted_number=str_pad($current_id[0], 8, "0", STR_PAD_LEFT);
      

         }
         else{
            $converted_number = str_pad($current_id[0]->batch_id+1, 8, "0", STR_PAD_LEFT);
         }

         
         
         $tripBatch = new TripBatches();
       
        switch ($paramString) {
            case 'dynamicRouting':
                
                $tripBatch->batch_no = 'DDT'.$converted_number;
                $tripBatch->created_by = $user_id;
                $tripBatch->store_id = $store_id;  
                $tripBatch->save();
                return $tripBatch;
           
                break;
            case 'customRouting':
                $tripBatch->batch_no = 'CDT'.$converted_number;
                $tripBatch->created_by = $user_id;
                $tripBatch->store_id = $store_id; 
                $tripBatch->save();
                return $tripBatch;
                break;   
            default:
                $tripBatch->batch_no = $converted_number;
                $tripBatch->created_by = $user_id;
                $tripBatch->store_id = $store_id; 
                $tripBatch->save();
                return $tripBatch;
                break;
        }
       
    }


    public function updateStoreConstraints($sConstraint,$constraintVal){
   
        $updateConstraint = TripBatches::find($sConstraint[0]['batch_id']);
        $updateConstraint->constraints = $constraintVal;
        $updateConstraint->save();
     
    }

    public function addStoreConstraints($data,$store_id,$userId){
        $dataArray = array(
        'store_id' => $store_id,
        'start_datetime' => $data['startDate'],
        'end_datetime' => $data['endDate'],
        'created_by' => $userId,
        'constraints' => json_encode($data['constraints'],true),
        'status' => 1
        );

        return $this->create($dataArray);
    }

    }
