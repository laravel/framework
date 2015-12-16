<?php

namespace Illuminate\Cache\Events;

class KeyForgotten
{
    /**
     * The key that was forgotten.
     *
     * @var string
     */
    public $key;

    /**
     * Create a new event instance.
     *
     * @param  string  $key
     * @return void
     */
    public function __construct($key)
    {
        $this->key = $key;
    }
}
