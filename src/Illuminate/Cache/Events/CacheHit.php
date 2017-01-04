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
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $value, array $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
        $this->value = $value;
    }
}
