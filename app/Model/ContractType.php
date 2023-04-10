<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ContractType as Validator;

class ContractType extends Model
{
	use Validator;
	protected $primaryKey = 'id';
	protected $table = 'contract_type';
	protected $fillable = [
		'contract_type_title',
		'customer_id',
		'key',
		'created_at',
		'updated_at'
	];
	public $timestamps = false;
}