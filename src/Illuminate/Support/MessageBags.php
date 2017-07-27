<?php

namespace Illuminate\Support;

use Countable;

class MessageBags implements Countable
{
    /**
     * The array of the view error bags.
     *
     * @var array
     */
    public $bags = [];

    /**
     * Create a new MessageBags instance.
     *
     * @param array $bags
     * @return void
     */
    public function __construct($bags)
    {
        $this->bags = $bags;
    }

    /**
     * Determine if messages exist for all of the given keys in any MessageBag.
     *
     * @param  array|string  $key
     * @return bool
     */
    public function have($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $messageKeys = [];

        foreach ($this->bags as $bag) {
            $messageKeys = array_merge($messageKeys, $bag->keys());
        }

        return count($keys) == count(array_intersect($keys, $messageKeys));
    }

    /**
     * Determine if messages exist for any of the given keys in any MessageBag.
     *
     * @param  array|string  $key
     * @return bool
     */
    public function haveAny($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $messageKeys = [];

        foreach ($this->bags as $bag) {
            $messageKeys = array_merge($messageKeys, $bag->keys());
        }

        return ! empty(array_intersect($keys, $messageKeys));
    }

    /**
     * Get the number of message bags in the container.
     *
     * @return int
     */
    public function count()
    {
        return count($this->bags);
    }
}
