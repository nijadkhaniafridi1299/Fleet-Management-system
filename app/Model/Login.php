<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Login as Validator;

class Login extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "users";
    protected $fillable = [
     'name',
     'password',
    
    
     ];
  

 
    }

