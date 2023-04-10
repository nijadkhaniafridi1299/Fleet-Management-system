<?php

namespace App\Model;

use App\Model;
use App\Message\Error;
use App\Validator\Promotion as Validator;

class Promotion extends Model{
    use Validator;

    const IMAGE_PATH = 'public/uploads/promo/';

    protected $primaryKey = "promotion_id";
    protected $table = "promotions";
    protected $fillable = ['start_date', 'end_date', 'min_quantity', 'promotion_type', 'on_products',
                            'locations', 'status', 'web_image', 'mobile_image',
                            'range_limit', 'group_id', 'channel_id', 'add_on','is_banner'
                          ];
    protected $attributes = [
        "status" => 1,
        "min_quantity" => 0,
        "add_on" => 0,
        "on_products" => "[]",
        "locations" => "[]",
        "gift_products" => "[]",
        "range_limit" => Null,
        "group_id" => 1,
        "channel_id" => 1

    ];
    public $timestamps = true;

    function group(){
      return $this->belongsTo('App\Model\CustomerGroup', 'group_id', 'group_id');
    }
    function channel(){
      return $this->belongsTo('App\Model\Channel', 'channel_id', 'channel_id');
    }

    function add($data){

      $data['on_products'] = isset($data['on_products']) && is_array($data['on_products']) ? $data['on_products'] : [];
      $data['on_variants'] = isset($data['on_variants']) && is_array($data['on_variants']) ? $data['on_variants'] : [];
      $data['locations'] = isset($data['locations']) && is_array($data['locations']) ? $data['locations'] : [];
      // $data['gift_products'] = isset($data['gift_products']) && is_array($data['gift_products']) ? $data['gift_products'] : [];
      $data['gift_variants'] = isset($data['gift_variants']) && is_array($data['gift_variants']) ? $data['gift_variants'] : [];

      $data['on_products'] = json_encode($data['on_products'], JSON_UNESCAPED_UNICODE);
      $data['on_variants'] = json_encode($data['on_variants'], JSON_UNESCAPED_UNICODE);
      $data['locations'] = json_encode($data['locations'], JSON_UNESCAPED_UNICODE);
      // $data['gift_products'] = json_encode($data['gift_products'], JSON_UNESCAPED_UNICODE);
      $data['gift_variants'] = json_encode($data['gift_variants'], JSON_UNESCAPED_UNICODE);

      if($data['range_limit'] != Null){
        $data['range_limit'] = json_encode($data['range_limit']);
      }
      if($data['min_quantity'] == Null){
        $data['min_quantity'] = 0;
      }
      if($data['add_on'] == Null){
        $data['add_on'] = 0;
      }
      if($data['is_offer'] == Null){
        $data['is_offer'] = 0;
      }

      //dd($data);
      try{
          return parent::add($data);
      }
      catch(\Exception $ex){
          Error::trigger("promotion.add", [$ex->getMessage()]);
      }
    }

    function change(array $data, $promotion_id){

      $promotion = static::find($promotion_id);

      $data['on_products'] = isset($data['on_products']) && is_array($data['on_products']) ? $data['on_products'] : [];
      $data['locations'] = isset($data['locations']) && is_array($data['locations']) ? $data['locations'] : [];
      // $data['gift_products'] = isset($data['gift_products']) && is_array($data['gift_products']) ? $data['gift_products'] : [];

      //  echo '<pre>'.print_r($data, true).'</pre>'; exit;

      $data['on_products'] = json_encode($data['on_products'], JSON_UNESCAPED_UNICODE);
      $data['locations'] = json_encode($data['locations'], JSON_UNESCAPED_UNICODE);
      // $data['gift_products'] = json_encode($data['gift_products'], JSON_UNESCAPED_UNICODE);

      return parent::change($data, $promotion_id);
    }

    function bannerUpload($file){

        // $name = $file->getClientOriginalName();
        // $name = str_replace('.' . $file->getClientOriginalExtension(), '', $file->getClientOriginalName());

        // $_name = preg_replace('#[^A-Za-z0-9_\-]#', '-', $name);

        $counter = '';

        do {

          //  $name = $_name . $counter . '.' . $file->getClientOriginalExtension();
           $name = 'promotion_'.date('Ymd').uniqid().'.'.$file->getClientOriginalExtension();

           $counter = (int) $counter;

           $counter++;

        } while(file_exists(public_path() .  '/uploads/promo/' . $name));

        $destinationPath = public_path() .  self::IMAGE_PATH;
        $is_Uploaded = $file->move(public_path().'/uploads/promo/', $name);
        if($is_Uploaded){
            return self::IMAGE_PATH . $name;
        }
    }

}
