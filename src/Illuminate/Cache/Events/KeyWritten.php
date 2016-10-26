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
     * The tags that were assigned to the key.
     *
     * @var array
     */
    public $tags;

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
        $this->key = $key;
        $this->tags = $tags;
        $this->value = $value;
        $this->minutes = $minutes;
    }
}
