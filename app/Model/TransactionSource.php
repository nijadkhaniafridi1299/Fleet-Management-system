<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ContractType as Validator;

class TransactionSource extends Model
{
	use Validator;
	protected $primaryKey = 'id';
	protected $table = 'transaction_sources';
	protected $fillable = [
		'transaction_source_id',
		'title',
		'key',
		'created_at',
		'updated_at'
	];
	public $timestamps = false;
}