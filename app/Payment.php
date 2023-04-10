<?php

namespace App;

abstract class Payment{

    abstract function adminForm();
    abstract function adminSettingSave();
    abstract function form();
    abstract function process();
    // abstract function information($transaction_id, $order_id, $payment_key);

    function getLabels(){

    }

    static function listAvailableMethods(){
        $path = __DIR__ . "/Payment/";
        $handle = opendir($path);

        $paymentMethods = $implementables = [];

        $self = new \ReflectionClass(__CLASS__);
        $abstractMethods = $self->getMethods(\ReflectionMethod::IS_ABSTRACT);

        for($i=0, $count = count($abstractMethods); $i < $count; $i++){
            $implementables[] = $abstractMethods[$i]->name;
        }


      //  echo '<pre>'.print_r($abstractMethods, true).'</pre>'; exit;

        while( $file = readdir($handle)){

            $toBeImplemented = $implementables;

            $methods = $_methods = [];

            if(!preg_match('#.php$#', $file)){
                continue;
            }



            /*
            $php_code = file_get_contents($path . $file);
            $classes = get_php_classes($php_code);
            */

            $php = file_get_contents($path . $file);


            //preg_match("#namespace ([\w+\\])+#sim", $php, $namespace);
            preg_match("#class (\w+)#sim", $php, $class);

            $className = "\\" . __NAMESPACE__ . "\\Payment\\" . $class[1];


            try{

               // $paymentMethod = new \ReflectionClass($className);
            //  echo $className . '<br>'; break;
              //  $methods = $paymentMethod->getMethods();

                //$methods = get_class_methods($className);

                preg_match_all("#function\s+(\w+)#", $php, $matches);

                /*echo '<pre>'.print_r($matches, true).'</pre>'; continue;

                for($i=0, $count = count($methods); $i < $count; $i++){
                    $_methods[] = $methods[$i]->name;
                }
                */
                $diff = array_diff($implementables, $matches[1]);

                if(count($diff) == 0){
                    $paymentMethods['ready'][] = \App\Singleton::getObject($className);
                }
                else {
                    $paymentMethods['uncompatable'][] = $className;
                }


                //echo '<pre>'.print_r($diff, true).print_r($matches[1], true).print_r($implementables, true).'</pre>';
                //exit;

            }
            catch(\Symfony\Component\Debug\Exception\FatalErrorException $ex){
                echo $ex->getMessage(); exit;
            }

        }

        return $paymentMethods;
    }

}
