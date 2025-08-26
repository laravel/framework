<?php

namespace Illuminate\Cache\Events;

class KeyWriteFailed extends CacheEvent
{
    /**
     * The value that would have been written.
     */
    public $value;

    /**
     * The number of seconds the key should have been valid.
     *
     * @var int|null
     */
    public $seconds;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @param  string  $key
     * @param  int|null  $seconds
     * @param  array  $tags
     */
    public function __construct($storeName, $key, $value, $seconds = null, $tags = [])
    {
        parent::__construct($storeName, $key, $tags);

        $this->value = $value;
        $this->seconds = $seconds;
    }
}
