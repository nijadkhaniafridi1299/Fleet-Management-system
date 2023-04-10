<?php

namespace App\Model;

use App\Model;
use App\Message\Error;


class CronJobs extends Model
{

    protected $primaryKey = "id";
    protected $table = "cron_job";
    protected $fillable = [
        'id',
        'last_run',
        'name',
        'cron_time',
        'recurring',
       

    ];

    
  

    function  add($data){

        try{
            return  parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("CronJobs.add", [$ex->getMessage()]) ;
        }
    }

    function change(array $data, $delivery_id){

        try{
            parent::change($data, $delivery_id);
        }
        catch(Exception $ex){
            Error::trigger("CronJobs.change", [$ex->getMessage()]) ;
        }

    }

    public function addCronJob($data,$store_id,$userId){

       
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

    public function getCronJobs(){
        return $this->get()->where('is_done',false)->where('')->toArray();

    }    
}

