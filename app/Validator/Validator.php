<?php

namespace App\Validator;
use Validator as CoreValidator;

trait Validator{

    protected $errors = array();

    public function validate($data, array $rules = [], array $messages = [])
    {

        if(count($rules) == 0) $rules = $this->rules;
        if(count($messages) == 0) $messages = $this->messages;



        if (!isset($messages)) {
            $messages = [];
        }

        $v = CoreValidator::make($data, $rules, $messages);

        if ($v->fails())
        {

            $this->errors = $v->errors();

            return false;
        }

        return true;
    }

    function getErrors(){



        $errors = [];



        if (isset($this->errors)) {
            foreach($this->errors->toArray() as $source => $error){
                $errors[$source] = (is_array($error)) ? implode(". ", $error) : $error;
            }
        }

        return $errors;
    }

}
