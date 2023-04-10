<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Channel as Validator;

class Channel extends Model
{
  use Validator;

  protected $primaryKey = "channel_id";
  protected $table = "channels";
  protected $fillable = ['channel_name', 'parent_id', 'status', 'channel_code'];
  protected $attributes = ['status'=> 1];
  public $timestamps = true;


  function ChannelProducts(){
    return $this->hasMany('App\Model\ChannelProductPricing', 'channel_id', 'channel_id')->where('status', 1);
  }

  function ChannelProductsAll(){
    return $this->hasMany('App\Model\ChannelProductPricing', 'channel_id', 'channel_id');
  }

  function ChannelCustomer(){
    return $this->hasMany('App\Model\Customer', 'channel_id', 'channel_id');
  }

  function parent(){
    return $this->belongsTo('App\Model\Channel', 'parent_id', 'channel_id');
  }
  function children(){
    return $this->hasMany('App\Model\Channel', 'parent_id');
  }

  function add($data) {

    $data['channel_name']['en'] = cleanNameString($data['channel_name']['en']);

    if (!isset($data['channel_name']['en']) || $data['channel_name']['en'] == '') {
      Error::trigger("channel.add", ["Please Enter Name in English. Special Characters are not allowed."]);
      return false;
    }

    $data['channel_name']['ar'] = cleanNameString($data['channel_name']['ar']);

    if (!isset($data['channel_name']['ar']) || $data['channel_name']['ar'] == '') {
      Error::trigger("channel.add", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
      return false;
    }

    $data['channel_code'] = (string) $data['channel_name']['en'];

    $data['channel_name'] = array_filter($data['channel_name']);
    $data['channel_name'] = json_encode($data['channel_name'], JSON_UNESCAPED_UNICODE);

    unset($data['product_ids']);
    unset($data['products']);

    try {
      $channel = parent::add($data);
      return $channel;
    }
    catch(\Exception $ex){
      Error::trigger("channel.add", [$ex->getMessage()]) ;
    }
  }

  function change(array $data, $channel_id){

    $channel = static::find($channel_id);

    $data['channel_name']['en'] = cleanNameString($data['channel_name']['en']);

    if (!isset($data['channel_name']['en']) || $data['channel_name']['en'] == '') {
      Error::trigger("channel.change", ["Please Enter Name in English. Special Characters are not allowed."]);
      return false;
    }

    $data['channel_name']['ar'] = cleanNameString($data['channel_name']['ar']);

    if (!isset($data['channel_name']['ar']) || $data['channel_name']['ar'] == '') {
      Error::trigger("channel.change", ["Please Enter Name in Arabic. Special Characters are not allowed."]);
      return false;
    }

    $channel->channel_code = (string) $data['channel_name']['en'];

    $data['channel_code'] = (string) $data['channel_name']['en'];
    $data['channel_name'] = array_filter($data['channel_name']);
    $data['channel_name'] = json_encode($data['channel_name'], JSON_UNESCAPED_UNICODE);

    $channel->channel_name = json_encode($data['channel_name'], JSON_UNESCAPED_UNICODE);

    unset($data['product_ids']);
    unset($data['products']);

    if (!isset($data['parent_id'])) {
      $channel->parent_id = Null;
    } else {
      $channel->parent_id = $data['parent_id'];
    }

    try {
      return parent::change($data, $channel_id);
    } catch(Exception $ex) {
      Error::trigger("channel.change", [$ex->getMessage()]);
    }
  }
}
