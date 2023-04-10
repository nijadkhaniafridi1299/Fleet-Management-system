<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ContractStatus as Validator;

class ContractStatus extends Model
{
	use Validator;
	protected $primaryKey = 'id';
	protected $table = 'contract_status';
	protected $fillable = [
		'contract_status_title',
		'customer_id',
		'key',
		'sequence',
		'created_at',
		'updated_at'
	];
	public $timestamps = false;
}