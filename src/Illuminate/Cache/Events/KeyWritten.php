<?php

namespace Illuminate\Cache\Events;

class KeyWritten
{
    /**
     * The key that was written.
     *
     * @var string
     */
    public $key;

    /**
     * The value that was written.
     *
     * @var mixed
     */
    public $value;

    /**
     * The number of minutes the key should be valid.
     *
     * @var int
     */
    public $minutes;

    /**
     * Create a new event instance.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $minutes
     * @return void
     */
    public function __construct($key, $value, $minutes)
    {
        $this->key = $key;
        $this->value = $value;
        $this->minutes = $minutes;
    }
}
