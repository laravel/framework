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
     * Any tags that were used.
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
        $this->value = $value;
        $this->minutes = $minutes;
        $this->tags = $tags;
    }
}
