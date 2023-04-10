<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\DocumentType as Validator;
use DB;

class DocumentType extends Model
{
    use Validator;
    protected $primaryKey = "document_type_id";
    protected $table = "document_types";
    protected $fillable = [
      'title',
      'status',
      'deleted_at',
      'created_at',
      'updated_at'
    ];
    protected $attributes = ["status" => 1];
    public $timestamps = true;
    
    }
