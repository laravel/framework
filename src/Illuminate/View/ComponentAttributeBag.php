<?php

namespace Illuminate\View;

use ArrayAccess;
use Illuminate\Support\Arr;
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
    public function get($key, $default = null)
    {
        return $this->attributes[$key] ?? value($default);
    }

    /**
     * Get a given attribute from the attribute array.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            $values = $this->attributes;
        } else {
            $keys = Arr::wrap($keys);

            $values = Arr::only($this->attributes, $keys);
        }

        return new static($values);
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributes
     * @return static
     */
    public function merge(array $attributeDefaults = [])
    {
        return new static(
            collect($this->attributes)->map(function ($value, $key) use ($attributeDefaults) {
                if ($value === true) {
                    return $key;
                }

                return collect([$attributeDefaults[$key] ?? '', $value])
                                ->filter()
                                ->unique()
                                ->join(' ');
            })->filter()->all()
        );
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
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributes
     * @return static
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
        return $this->get($offset);
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
        return (string) new HtmlString(
            collect($this->attributes)->map(function ($value, $key) {
                return $value === true
                        ? $key
                        : $key.'="'.str_replace('"', '\\"', trim($value)).'"';
            })->implode(' ')
        );
    }
}
