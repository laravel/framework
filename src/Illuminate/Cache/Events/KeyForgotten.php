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
     * Any tags that were used.
     *
     * @var array
     */
    public $tags;

    /**
     * Create a new event instance.
     *
     * @param  string  $key
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
    }
}
