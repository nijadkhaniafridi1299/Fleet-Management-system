<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Skip as Validator;

class Skip extends Model
{
    use Validator;

    protected $primaryKey = "skip_id";
    protected $table = "skips";
    protected $fillable = [
        'customer_id',
        'material_id',
        'address_id',
        'imei',
        'sim_card_number',
        'address_id',
        'current_level',
        'current_skip_level_id',
        'prev_skip_level_id',
        'asset_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected $attributes = ['status' => 1, 'created_by'=>0];

    function customer() {
        return $this->belongsTo('App\Model\Customer', 'customer_id');
    }

    function address() {
        return $this->belongsTo('App\Model\Address', 'address_id');
    }

    function material() {
        return $this->belongsTo('App\Model\Material', 'material_id');
    }

    function current_skip_level() {
        return $this->belongsTo('App\Model\SkipLevel', 'current_skip_level_id', 'skip_level_id');
    }

    function asset_inventory() {
        return $this->belongsTo('App\Model\AssetInventory', 'asset_id', 'asset_id')->select('asset_id','title','allocated','assigned_to','assignee_id','service_category_id');
    }

    function asset_transaction() {
        return $this->hasOne('App\Model\AssetTransaction', 'asset_id', 'asset_id')
                    ->select('asset_id','transaction_date','no_of_days','transaction_type','remarks')
                    ->latest('created_at');
    }
   
    // public function service_cat()
    // {
    //     return $this->asset_inventory->service_category;
    // }
    function add($data) {

        try {
            return parent::add($data);
        }
        catch(\Exception $ex){
            Error::trigger("skip.add", [$ex->getMessage()]);
        }
    }

    function change(array $data, $skip_id) {
        $skip = Skip::find($skip_id);

        if (isset($data['skip_password'])) {
			$data['skip_password'] = Hash::make($data['skip_password']);

            if (array_key_exists('old_password', $data)) {
                unset($data['old_password']);
            }
		}

		if (!isset($data['skip_password'])) {
			$data['skip_password'] = $data['old_password'];
            if (array_key_exists('old_password', $data)) {
                unset($data['old_password']);
            }
		}

        if (isset($data['imei']) && $data['imei'] == $skip->imei) {
            unset($data['imei']);
        }

        if (isset($data['sim_card_number']) && $data['sim_card_number'] == $skip->sim_card_number) {
            unset($data['sim_card_number']);
        }

        try {
            return parent::change($data, $skip_id);
        }
        catch(Exception $ex) {
            Error::trigger("skip.change", [$ex->getMessage()]);
        }
    }
}
