<?php

namespace App\Events;

class GPSAntennaCutEvent extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $data;
    public function __construct($data)
    {
        
        $this->data=$data;
    }
}
