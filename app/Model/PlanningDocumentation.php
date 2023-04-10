<?php

namespace App\Model;

use App\Model;
use App\Validator\PlanningDocumentation as Validator;
use DB;
use Auth;

class PlanningDocumentation extends Model
{
    use Validator;

    protected $primaryKey = "id";
    protected $table = "planning_documentation";
    protected $fillable = ['attachment','remarks','description','status','deleted_at','order_id'];
    public $timestamps = true;

    function getDocList($order_id){
        return $list = PlanningDocumentation::where('order_id',$order_id)->whereNotNull('attachment')->get()->toArray();
    }
    function upload($file, $entity = 'document',$order_id) {
        
        $public_path = base_path('public');
       
		$name = $file->getClientOriginalName();
		$name = str_replace("." . $file->getClientOriginalExtension(), "", $file->getClientOriginalName());

		$_name = preg_replace('#[^A-Za-z0-9_\-]#', '-', $name);

		$counter = '';
		$path = $public_path . '/docs/' . $entity . '/';

        if (!file_exists($path)) {
            if( !\file_exists($public_path . '/docs/') ){
                mkdir($public_path . '/docs/');
            }

            mkdir($path);
        }

        do { 

           $name = $order_id.'_'.date('Ymd').uniqid().'.'.$file->getClientOriginalExtension();
           $counter = (int) $counter;

           $counter++;

        } while(file_exists( $path . $name));

        $isUploaded = $file->move($path, $name);

        return '/docs/' . $entity . '/' . $name;
	}
}
