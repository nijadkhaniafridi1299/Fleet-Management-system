<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Tool as Validator;

class Tool extends Model
{
  use Validator;

  protected $primaryKey = "tool_id";
  protected $table = "tools";
  protected $fillable = ['tool_id', 'name'];
  public $timestamps = true;

}
