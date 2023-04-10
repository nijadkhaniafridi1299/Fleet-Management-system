<?php

namespace App\Model;
use App\Payment\HyperPay;
use App\Message\Error;

class MobilePayment extends HyperPay{

    public function processPayment($amount,$transaction_id)
    {
    $errors = array();
    $mobile = 1;
    $req = $this->processPy($amount,$transaction_id);
        return $req;
    }
}
