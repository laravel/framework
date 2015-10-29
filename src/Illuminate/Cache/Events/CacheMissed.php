<?php

namespace Illuminate\Cache\Events;

class CacheMissed
{
    /**
     * THe key that was missed.
     *
     * @var string
     */
    public $key;

    /**
     * Create a new event instance.
     *
     * @param  string  $event
     * @return void
     */
    public function __construct($key)
    {
        $this->key = $key;
    }
}
