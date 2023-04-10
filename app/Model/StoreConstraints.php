<?php

namespace App\Model;

use App\Model;
use App\Message\Error;

use Carbon\Carbon;

class StoreConstraints extends Model
{
   
    public $timestamps = false;
    protected $primaryKey = "store_constraint_id";
    protected $table = "store_constraints";
    protected $fillable = [
        'store_id',
        'start_datetime',
        'end_datetime',
        'created_by',
        'status',
        'constraints'

    ];


  

    function  add($data){

        try{
            return  parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("StoreConstraints.add", [$ex->getMessage()]) ;
        }
    }

    function change(array $data, $delivery_id){

        try{
            parent::change($data, $delivery_id);
        }
        catch(Exception $ex){
            Error::trigger("StoreConstraints.change", [$ex->getMessage()]) ;
        }

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

    public function updateStoreConstraints($sConstraint,$constraintVal){
        $updateConstraint = StoreConstraints::find($sConstraint[0]['store_constraint_id']);
        $updateConstraint->constraints = $constraintVal;
        $updateConstraint->updated_at = Carbon::now();
        $updateConstraint->save();
    }

}
