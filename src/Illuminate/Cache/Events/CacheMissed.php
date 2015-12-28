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
     * The tags that were assigned to the key.
     *
     * @var array
     */
    public $tags;

    /**
     * Create a new event instance.
     *
     * @param  string  $event
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, array $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
    }
}
