<?php

namespace Illuminate\Cache\Events;

class CacheHit
{
    /**
     * The key that was hit.
     *
     * @var string
     */
    public $key;

    /**
     * The value that was retrieved.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new event instance.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
