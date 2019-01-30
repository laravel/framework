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
     * The number of seconds the key should be valid.
     *
     * @var int|null
     */
    public $seconds;

    /**
     * Create a new event instance.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|null  $seconds
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $value, $seconds = null, $tags = [])
    {
        parent::__construct($key, $tags);

        $this->value = $value;
        $this->seconds = $seconds;
    }
}
