<?php

namespace Illuminate\Redis\Lua;

use Illuminate\Support\Arr;

/**
 * Represents arguments and keys for a Lua script.
 * Provides methods to create a new instance with empty or given keys/arguments, and to get/set them.
 */
class LuaScriptArguments
{
    /**
     * An array of keys to be passed to the Lua script.
     *
     * @var string[]
     */
    private $keys;

    /**
     * An array of arguments to be passed to the Lua script.
     *
     * @var string[]
     */
    private $arguments;

    /**
     * @param  string[]  $keys  An array of keys to be passed to the Lua script.
     * @param  string[]  $arguments  An array of arguments to be passed to the Lua script.
     */
    private function __construct($keys = [], $arguments = [])
    {
        $this->keys = $keys;
        $this->arguments = $arguments;
    }

    /**
     * Create a new instance with empty keys and arguments.
     *
     * @return self
     */
    public static function empty()
    {
        return new self;
    }

    /**
     * Create a new instance of the LuaScriptArguments class with the given keys and arguments.
     *
     * @param  string[]  $keys  An array of keys to be passed to the Lua script.
     * @param  string[]  $arguments  An array of arguments to be passed to the Lua script.
     * @return self
     *
     * @throws \InvalidArgumentException if $keys or $arguments is empty or null.
     */
    public static function with($keys, $arguments)
    {
        if (empty($keys) && empty($arguments)) {
            throw new \InvalidArgumentException('The parameter $keys and $arguments cannot be null or empty.');
        }

        return new self($keys, $arguments);
    }

    /**
     * Create a new instance of the LuaScriptArguments class with the given keys.
     *
     * @param  string[]  $keys  An array of keys to be passed to the Lua script.
     * @return self
     *
     * @throws \InvalidArgumentException if $keys is empty or null.
     */
    public static function withKeys(...$keys)
    {
        if (empty($keys)) {
            throw new \InvalidArgumentException('The parameter $keys cannot be null or empty.');
        }

        return new self($keys);
    }

    /**
     * Create a new instance of the LuaScriptArguments class with the given arguments.
     *
     * @param  string[]  $arguments  An array of arguments to be passed to the Lua script.
     * @return self
     *
     * @throws \InvalidArgumentException if $arguments is empty or null.
     */
    public static function withArguments(...$arguments)
    {
        if (empty($arguments)) {
            throw new \InvalidArgumentException('The parameter $arguments cannot be null or empty.');
        }

        return new self([], $arguments);
    }

    /**
     * @return int
     */
    public function getNumberOfKeys()
    {
        return count($this->keys);
    }

    /**
     * Collapse keys and argument to single array.
     *
     * @return string[] The array ready to pass to redis
     */
    public function toArray()
    {
        return Arr::collapse([$this->keys, $this->arguments]);
    }

    /**
     * Get the array of keys to be passed to the Lua script.
     *
     * @return string[] An array of keys to be passed to the Lua script.
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Set the array of keys to be passed to the Lua script.
     *
     * @param  string[]  $keys  An array of keys to be passed to the Lua script.
     * @return self
     *
     * @throws \InvalidArgumentException if $keys is empty or null.
     */
    public function setKeys($keys)
    {
        if (empty($keys)) {
            throw new \InvalidArgumentException('The parameter $keys cannot be null or empty.');
        }

        $this->keys = $keys;

        return $this;
    }

    /**
     * Get the array of arguments to be passed to the Lua script.
     *
     * @return string[] An array of arguments to be passed to the Lua script.
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Set the array of arguments to be passed to the Lua script.
     *
     * @param  string[]  $arguments  An array of arguments to be passed to the Lua script.
     * @return self
     *
     * @throws \InvalidArgumentException if $arguments is empty or null.
     */
    public function setArguments($arguments)
    {
        if (empty($arguments)) {
            throw new \InvalidArgumentException('The parameter $arguments cannot be null or empty.');
        }

        $this->arguments = $arguments;

        return $this;
    }
}
