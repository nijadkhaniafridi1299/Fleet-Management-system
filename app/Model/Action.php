<?php
namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Action as Validator;

class Action extends Model
{
	use Validator;
	protected $primaryKey = 'action_id';
	protected $table = 'actions';
	protected $fillable = ['action_detail','key'];
	public $timestamps = true;
}