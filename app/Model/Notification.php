<?php
namespace App\Model;

use App\Model;
use App\Message\Error;

class Notification extends Model
{
	protected $primaryKey = 'notification_id';
	protected $table = 'notifications';
	protected $fillable = ['user_id','notification_body','reference_id','is_sent','to_source', 'user_id','reference_type', 'is_read'];
	public $timestamps = true;

	public function saveNotification($dataArray){
		//dd($dataArray);
		$model = new Notification();
		//$model->origin_id = $dataArray['origin_id'];
		$model->user_id =  $dataArray['user_id'];
		$model->type =  $dataArray['type'];
		$model->notification_body =  $dataArray['notification_body'];
		$model->reference_id =  $dataArray['reference_id'];
		$model->is_sent = $dataArray['is_sent'];
		try{
			$model->save();
			return $model;
		}
		catch(Exception $ex){
			Error::trigger("notification.add", [$ex->getMessage()]) ;
			return false;
		}
	}
}
