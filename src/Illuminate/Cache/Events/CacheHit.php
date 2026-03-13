<?php

namespace Illuminate\Cache\Events;

class CacheHit extends CacheEvent
{
    /**
     * The value that was retrieved.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new event instance.
     */
    public function __construct(?string $storeName, string $key, mixed $value, array $tags = [])
    {
        parent::__construct($storeName, $key, $tags);

        $this->value = $value;
    }
}
