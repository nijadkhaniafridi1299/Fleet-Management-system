<?php

namespace App\Notification;
// use Notification;
use Illuminate\Support\Facades\Notification;

class SMS extends Notification{

  protected $url = 'http://basic.unifonic.com/rest/SMS/messages';
  protected $applicationType = '68';
  protected $appId = '';
  protected $userName = '';
  protected $password = '';
  protected $sender = '';
  protected $domain = "";
  protected $numbers = [];
  protected $message;
  protected $messageID;
  protected $timeSend = 0;
  protected $dateSend = 0;
  protected $deleteKey = 152485;
  protected $lang = 3;
  protected $responseCodes = [
    "1" => "Error Occured",
    "2" => "Your balance is 0.",
    "3" => "Your balance is not enough.",
    "4" => "Invalid mobile number (invalid username or apiKey).",
    "5" => "Invalid password.",
    "6" => "SMS-API not responding, please try again.",
    "13" => "Sender name is not accepted.",
    "14" => "Sender name is not active from Alfa-cell.com and mobile telecommunications companies.",
    "15" => "Mobile(s) number(s) is not specified or incorrect.",
    "16" => "Sender name is not specified.",
    "17" => "Message text is not specified or not encoded properly with Alfa-cell.com Unicode.",
    "18" => "Sending SMS stopped from support.",
    "19" => "applicationType is not specified or invalid.",
    "101" => "Sending by API is disabled.",
    "102" => "Your IP is not authorized to using the API.",
    "103" => "This country is not authorized to using the API."
  ];

  function __construct(array $numbers, $message){
    $this->numbers = $numbers;
    $this->messageID = rand(1,99999);
    $this->message = $message;
    $this->sender = urlencode($this->sender);
  }


  function sendUsingCUrl(){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $stringToPost);
    // try{
    $result = curl_exec($ch);
    //}

  }

function sendPost(){

  /*
  $post = [
    "AppSid" => $this->appId,
    "Body" => $this->message,
    "Recipient" => implode(',', $this->numbers ),
    "SenderID" => $this->sender,
  ];

  $headers = array(
    'Authorization: Basic '. base64_encode("{$this->userName}:{$this->password}"),
    'Content-Type: application/json; charset=UTF-8'
  )
  ;*/

    $numbers = implode(',', $this->numbers );
    $post_data = "AppSid={$this->appId}&Body={$this->message}&Recipient={$numbers}&SenderID={$this->sender}&baseEncode=false&encoding=GSM7";

    $headers = array(
      'Accept: application/json',
      'Authorization: Basic '. base64_encode("{$this->userName}:{$this->password}"),
      'Content-Type: application/x-www-form-urlencoded'
    );

  $curl = curl_init();
  curl_setopt_array($curl, array(
  CURLOPT_URL => $this->url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => $headers,
  CURLOPT_POSTFIELDS => $post_data,
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$response = json_decode($response,true);

curl_close($curl);

return $response;
}

function getResponseMessage($code){
  try {
    return $this->responseCodes[$code];
  } catch (\Exception $e) {
    return $this->responseCodes[1];
  }

}

function send(){
  return $this->sendPost();
}

}
