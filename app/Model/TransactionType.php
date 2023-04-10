<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ContractType as Validator;

class TransactionType extends Model
{
	use Validator;
	protected $primaryKey = 'transaction_type_id';
	protected $table = 'transaction_types';
	protected $fillable = [
		'name',
		'key',
		'sequence',
		'created_at',
		'updated_at'
	];
	public $timestamps = false;
}