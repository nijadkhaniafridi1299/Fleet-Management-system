<?php

namespace App\Model;

use App\Model;
use App\Model\Customer;
use App\Message\Error;
use App\Validator\WalletTransaction as Validator;
use Carbon\Carbon;

class WalletTransaction extends Model{

	use Validator;

	protected $casts = [
		'customer_id' => 'int',
		'order_id' => 'int',
		'amount' => 'float',
		'available_amount' => 'float',
		'reason_id' => 'int',
		'mode' => 'string'
	];

	public $timestamps = false;

	protected $fillable = [
		'customer_id',
		'order_id',
		'type',
		'amount',
		'available_amount',
		'reference',
		'reason_id',
		'mode',
		'added_by',
		'status',
		'valid_till'
	];

	protected $attributes = ['status'=> 1];

	function reason(){
		return $this->belongsTo('App\Model\RefundReason', 'reason_id', 'id');
	}

	function order(){
		return $this->belongsTo('App\Model\Order','order_id','order_id');
	}

	function transactionType(){
		return $this->belongsTo('App\Model\WalletTransactionType', 'type', 'id');
	}

	function source(){
		return $this->belongsTo('App\Model\Source', 'added_by', 'source_id');
	}

	function customer(){
		return $this->belongsTo('App\Model\Customer', 'customer_id', 'customer_id');
	}

	function add($data)
	{
		try {
			return $this->create($data);
		}
		catch (\Exception $ex) {
			Error::trigger("wallet.add", [$ex->getMessage()]);
			return false;
		}

	}

	function edit($data,$id)
	{
		$model =  $this->find($id);
		$model->update($data);
		return $model;
	}

	function statusChange($id){

		$data =  $this->find($id);
		$status_check = $data->status;
		if ($status_check == 1) {
			$model = $this->where('user_id', $id)->update(['status'=> 9]);
			return $model;
		}else if ($status_check == 9) {
			$model = $this->where('user_id', $id)->update(['status'=> 1]);
			return $model;
		}
	}

	static function update_wallet($customer_id){
		$amount = \App\Model\WalletTransaction::where('customer_id', $customer_id)
		->where("type",1)
		->where('status',1)
		->where('mode','credit')
		->sum('available_amount');

		// $wallet_balance = static::where('customer_id',$customer_id)
		// ->where('type',1)
		// ->where('status',1)
		// ->where('mode','credit')
		// ->sum('available_amount');
		// $amount = 0;
		// foreach ($transactions as $key => $value) {
		// 	$amount = $amount+$value['available_amount'];
		// }
		// $amount= $transactions+$wallet_balance;

		Customer::where('customer_id',$customer_id)->update(['wallet'=>$amount]);
		return $amount;
	}

	function update_wallet_transaction($customer_id,$amount){
		$wallet = $amount;
		$transactions = static::where('customer_id',$customer_id)
		->where('type',2)
		->where('status',1)
		->where('mode','credit')
		->where('available_amount','>', 0)
		->where('valid_till', '>=', date('Y-m-d h:i:s'))
		->orderBy('created_at', 'ASC')
		->get();

		$wallet_balance = static::where('customer_id',$customer_id)
		->where('type',1)
		->where('status',1)
		->where(function ($query) {
			$query->where('available_amount','>', 0)
			->orWhere('available_amount','<', 0);
		})
		->where('mode','credit')
		->get();

		// dd(!$transactions->isEmpty(),$wallet_balance);
		if(!$transactions->isEmpty()){
			foreach ($transactions as $key => $value) {
				if($amount == 0){
					break;
				}

				if($value->available_amount <= $amount){
					$amount-= $value->available_amount;
					$value->available_amount = 0;
					$value->save();
				}
				else{
					$value->available_amount -= $amount;
					$amount = 0;
					$value->save();
				}
			}
		}
		if(!$wallet_balance->isEmpty() || $amount > 0){
			foreach ($wallet_balance as $key => $value) {
				if($amount == 0){
					break;
				}
				if($value->available_amount <= $amount){
					$amount-= $value->available_amount;
					$value->available_amount = 0;
					$value->save();
				}
				else{
					$value->available_amount -= $amount;
					$amount = 0;
					$value->save();
				}
			}
		}

		$cust = Customer::find($customer_id);
		if(($cust->wallet - $wallet) <= 0){
			$cust->wallet = 0;
		}
		else{
			$cust->wallet -= $wallet;
		}
		$cust->save();
		return true;
	}

	function update_wallet_discount($customer_id,$amount){
		$wallet = $amount;

		$wallet_discount = static::where('customer_id',$customer_id)
		->where('type',3)
		->where('status',1)
		->where(function ($query) {
			$query->where('available_amount','>', 0)
			->orWhere('available_amount','<', 0);
		})
		->where('mode','credit')
		->get();

		if(!$wallet_discount->isEmpty() || $amount > 0){
			foreach ($wallet_discount as $key => $value) {
				if($amount == 0){
					break;
				}
				if($value->available_amount <= $amount){
					$amount-= $value->available_amount;
					$value->available_amount = 0;
					$value->save();
				}
				else{
					$value->available_amount -= $amount;
					$amount = 0;
					$value->save();
				}
			}
		}

		$cust = Customer::find($customer_id);
		if(($cust->wallet - $wallet) <= 0){
			$cust->wallet = 0;
		}
		else{
			$cust->wallet -= $wallet;
		}
		$cust->save();
		return true;
	}

	function walletDetail($customer_id)
	{
		return static::with('order:order_number,order_id')->select('order_id','amount','mode as transaction_type','reference','created_at as sdt')->where('customer_id',$customer_id)->orderBy('id','desc')->get()->toArray();
	}

	static function get_discount($customer_id){
		$amount = \App\Model\WalletTransaction::where('customer_id', $customer_id)
		->where("type",3)
		->where('status',1)
		->where('mode','credit')
		->sum('available_amount');

		return $amount;
	}
}
