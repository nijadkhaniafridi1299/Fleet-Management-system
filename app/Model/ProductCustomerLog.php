<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\ProductCustomerLog as Validator;

class ProductCustomerLog extends Model
{
	use Validator;
	protected $primaryKey = 'id';
	protected $table = 'product_customer_log';
	// protected $fillable = ['action_detail','key'];
	public $timestamps = true;
}