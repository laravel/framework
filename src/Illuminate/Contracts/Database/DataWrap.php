<?php

namespace Illuminate\Contracts\Database;

interface DataWrap extends \ArrayAccess
{
    public function __construct($attributes);

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset);

    /**
     * Get the value for a given offset.
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset);

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key);

    /**
     * Unset an attribute on the model.
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key);

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key);

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value);

    /**
     * Convert the data to its string representation.
     *
     * @return string
     */
    public function __toString();

    /**
     * Convert the data to its json representation.
     *
     * @return string
     */
    public function toJson();
}
