<?php

namespace Illuminate\View;

use ArrayAccess;
use Illuminate\Support\HtmlString;

class ComponentAttributeBag implements ArrayAccess
{
    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new component attribute bag instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get a given attribute from the attribute array.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function only($key, $default = null)
    {
        return $this->attributes[$key] ?? value($default);
    }

    /**
     * Implode the given attributes into a single HTML ready string.
     *
     * @param  array  $attributes
     * @return string
     */
    public function merge(array $attributeDefaults = [])
    {
        return new HtmlString(collect($this->attributes)
                ->map(function ($value, $key) use ($attributeDefaults) {
                    if ($value === true) {
                        return $key;
                    }

                    $values = collect([$attributeDefaults[$key] ?? '', $value])
                                    ->filter()
                                    ->unique()
                                    ->join(' ');

                    return $key.'="'.str_replace('"', '\\"', trim($values)).'"';
                })->filter()->implode(' '));
    }

    /**
     * Set the underlying attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Implode the given attributes into a single HTML ready string.
     *
     * @param  array  $attributes
     * @return string
     */
    public function __invoke(array $attributeDefaults = [])
    {
        return $this->merge($attributeDefaults);
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get the value at the given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->only($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Remove the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Implode the attributes into a single HTML ready string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->merge();
    }
}
