<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ProductLog as Validator;

class ProductLog extends Model
{
	use Validator;
	protected $primaryKey = 'id';
	protected $table = 'product_log';
	// protected $fillable = ['action_detail','key'];
	public $timestamps = true;
}