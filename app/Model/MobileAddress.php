<?php
namespace App\Model;
use App\Model\Address as AppAddress;
use App\Model\Location;
use App\Message\Error;
class MobileAddress extends AppAddress{

  public function NewAddress(array $parm)
  {
    $address =  $this->add($parm);
    if(!is_object($address)){
      $errors[] = Error::get('address.add');
      return false;
    }
    else {
      return $address;
    }
  }
  public function getAddress_old($clientId){
    $address = Address::where('customer_id',$clientId)->where('addresses.status',1)
    ->join('locations', 'addresses.location_id', '=', 'locations.location_id')->where('locations.status',1)->get()->toArray();
    // $address = Address::with('location', 'location.parent')
    //                   ->whereHas('location', function($query) {
    //                     $query->where('status', 1);
    //                     })
    //                   ->whereHas('location.parent', function($query) {
    //                     $query->where('status', 1);
    //                     })
    //                   ->where("customer_id", $clientId)
    //                   ->where('status',1)
    //                   ->get()->toArray();
    if($address){
      return $this->printAddress($address);
    }
    else {
      return 0;
    }
  }
  public function getAddress($clientId){
    // $address = Address::where('customer_id',$clientId)->where('addresses.status',1)
    // ->join('locations', 'addresses.location_id', '=', 'locations.location_id')->where('locations.status',1)->get()->toArray();
    $address = Address::with('location', 'location.parent')
    ->whereHas('location', function($query) {
      $query->where('status', 1);
    })
    ->whereHas('location.parent', function($query) {
      $query->where('status', 1);
    })
    ->where("customer_id", $clientId)
    ->where('status',1)
    ->latest()->take(5)
    ->get()->toArray();
    //   dd($address);
    if($address){
      return $this->printAddress($address);
    }
    else {
      return 0;
    }
  }

  public function getAddressByMobile($mobile){
    // $address = Address::where('customer_id',$clientId)->where('addresses.status',1)
    // ->join('locations', 'addresses.location_id', '=', 'locations.location_id')->where('locations.status',1)->get()->toArray();
    $address = Address::with('location', 'location.parent')
    ->whereHas('location', function($query) {
      $query->where('status', 1);
    })
    ->whereHas('location.parent', function($query) {
      $query->where('status', 1);
    })
    ->where("mobile", $mobile)
    ->where('status',1)
    ->latest()->take(5)
    ->get()->toArray();
    //   dd($address);
    if($address){
      return $this->printAddress($address);
    }
    else {
      return 0;
    }
  }
  public function printAddress(array $data){
    $count = count($data);
    for($i=0; $i<$count;$i++){
      $ad[$i] = json_decode($data[$i]['map_info'], true);
      //$adTitle[$i] = json_decode($data[$i]['title'], true);
      $rows[] = [
        "add_id" => (string)$data[$i]['address_id'],
        "sap_id" => (($data[$i]['erp_id'] == NULL ) ? "" : (string)$data[$i]['erp_id']),
        "user_id" => $data[$i]['customer_id'],
        "add_area" => $data[$i]['location_id'],
        "add_name" => (($data[$i]['title'] == NULL || $data[$i]['title'] == "" ) ? "" : $data[$i]['title']),
        "add_detail" => $data[$i]['address'],
        "add_street_name" => "",
        "add_latitude" => (string)$ad[$i]['latitude'],
        "add_longitude" => (string)$ad[$i]['longitude'],
        "add_block" => "",
        "add_google_address" => "",
        "add_city" => (string)$data[$i]['location']['parent_id'],
        "add_type" => $data[$i]['type'],
        "add_country" => "1",
        "floor_no" => (string)$data[$i]['floor_no'],
        "house_no" => (string)$data[$i]['house_no']
      ];
    }
    return $rows;
  }
  public function printAddress_old(array $data){
    $count = count($data);
    for($i=0; $i<$count;$i++){
      $ad[$i] = json_decode($data[$i]['map_info'], true);
      //$adTitle[$i] = json_decode($data[$i]['title'], true);
      $rows[] = [
        "add_id" => (string)$data[$i]['address_id'],
        "sap_id" => (($data[$i]['erp_id'] == NULL ) ? "" : (string)$data[$i]['erp_id']),
        "user_id" => $data[$i]['customer_id'],
        "add_area" => $data[$i]['location_id'],
        "add_name" => (($data[$i]['title'] == NULL || $data[$i]['title'] == "" ) ? "" : $data[$i]['title']),
        "add_detail" => $data[$i]['address'],
        "add_street_name" => "",
        "add_latitude" => (string)$ad[$i]['latitude'],
        "add_longitude" => (string)$ad[$i]['longitude'],
        "add_block" => "",
        "add_floor" => "",
        "add_google_address" => "",
        "add_city" => (string)$data[$i]['parent_id'],
        "add_country" => "1"
      ];
    }
    return $rows;
  }

  public function remove($data){
    $re = new Address();
    return $re->statusChange($data);
  }

  static function getCityId($addrId){
    $address =  AppAddress::where('address_id',$addrId)->with('location')->first();
    if(is_object($address)){
      $address =  $address->toArray();

    }
    if(isset($address['location']['parent_id']) && !empty($address['location']['parent_id'])){
      $address = $address['location']['parent_id'];
    }
    else{
      $address = "";
    }

    return $address;
  }

  public function editAddress($data){
    return Address::where('address_id',$data['id'])->where('customer_id',$data['user_id'])->update(['title'=>$data['address_title']]);
  }

}
