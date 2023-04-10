<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\AssetTransaction as Validator;

class AssetTransaction extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "inv_transactions";
    public $timestamps = true;
    protected $fillable = [
        'id',
        'asset_id',
        'order_id',
        'representative_id',
        'no_of_days',
        'transaction_type',
        'remarks',
        'transfer_from',
        'transfer_to',
        'transaction_date',
        'created_at',
        'updated_at'
    ];


    function asset(){
        return $this->belongsTo('App\Model\AssetInventory', 'asset_id');
    }
    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex) {
            Error::trigger("assettransaction.add", [$ex->getMessage()]);
        }
    }
    // protected $attributes = ['status' => 1];

    

}
