<?php

namespace App;
use Validator as CoreValidator;

trait Validator{

    protected $errors = array();

    public function validate($data, $rules = [], array $messages = [])
    {
        // make a new validator object
        
        if(count($rules) == 0) $rules = $this->rules;
        if(count($messages) == 0) $messages = $this->messages;

        //dd($data);
        if (!isset($messages)) {
            $messages = [];
        }

        $v = CoreValidator::make($data, $rules, $messages);

        // check for failure
        if ($v->fails())
        {
            // set errors and return false
            $this->errors = $v->errors();
            return false;
        }
        
        // validation pass
        return true;
    }

    function getErrors(){
       //return $this->errors;

        $errors = [];
        if (isset($this->errors)) {
            foreach($this->errors->toArray() as $source => $error){
                $errors[$source] = (is_array($error)) ? implode(". ", $error) : $error;
            }
        }

        return $errors;
    }

}
