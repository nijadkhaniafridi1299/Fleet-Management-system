<?php

namespace App\Model;

use App\Model;
use App\Message\Error;

class FleetModule extends Model
{
	protected $primaryKey = "module_id";
	protected $table = "fm_modules";
	protected $fillable = ['title', 'key'];
	public $timestamps = true;
}
