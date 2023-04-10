<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SMS extends Notification
{
    use Queueable;
    // protected $url = 'http://basic.unifonic.com/rest/SMS/messages';
    // protected $applicationType = '68';
    // protected $appId = '2ni44FQFeanZ6C9hi5SKJYaQqWAkNT';
    // protected $userName = 'tayyeb@futuregates.net';
    // protected $password = 'FG@aj98765';
    // protected $sender = 'ALJAZIERAH';
    // protected $domain = "https://oms.aljazierah.com/";
    protected $numbers = [];
    protected $message;
    protected $messageID;
    protected $timeSend = 0;
    protected $dateSend = 0;
    protected $deleteKey = 152485;
    protected $lang = 3;
    protected $responseCodes = [
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

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */


     public function sendMessage()
     {
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
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => $headers,
      ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response = json_decode($response,true);

      curl_close($curl);
      return $response;
 
 
     }
     

     

      function getResponseMessage($code){
        return $this->responseCodes[$code];
      }
  
      function send(){
        return $this->sendPost();
      }
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
