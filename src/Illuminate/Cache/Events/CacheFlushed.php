<?php

namespace Illuminate\Cache\Events;

class CacheFlushed
{
    /**
     * The name of the cache store.
     *
     * @var string|null
     */
    public $storeName;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @return void
     */
    public function __construct($storeName)
    {
        $this->storeName = $storeName;
    }
}
