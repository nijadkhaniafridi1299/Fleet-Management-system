<?php
namespace App\Model;

use App\Model;
use App\Model\ContractStatus;
use App\Model\ContractType;
use App\Message\Error;
use App\Validator\Contract as Validator;

class Contract extends Model
{
	use Validator;
	protected $primaryKey = 'contract_id';
	protected $table = 'contracts';
	protected $fillable = [
		'contract_number',
		'contract_type',
		'total_sale_price',
		'vat',
		'balance_due',
		'total_weight',
		'material_location',
		'no_of_lots',
		'start_date',
		'end_date',
		'contract_status',
		'payment_terms',
		'authorized_by(seller)',
		'designation(seller)',
		'authorized_by(buyer)',
		'designation(buyer)',
		'Custom_duties_applied',
		'status',
		'created_at',
		'updated_at'
	];
	public $timestamps = false;

	function contract_status(){
        return $this->belongsTo('App\Model\ContractStatus', 'contract_status', 'id');
    }

	function contract_type(){
        return $this->belongsTo('App\Model\ContractType', 'contract_type', 'id');
    }
}