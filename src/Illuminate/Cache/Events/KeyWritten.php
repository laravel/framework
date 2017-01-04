<?php

namespace Illuminate\Cache\Events;

class KeyWritten extends CacheEvent
{
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
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $value, $minutes, $tags = [])
    {
        parent::__construct($key, $tags);

        $this->value = $value;
        $this->minutes = $minutes;
    }
}
