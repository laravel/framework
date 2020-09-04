<?php

namespace Illuminate\View;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use IteratorAggregate;

class ComponentAttributeBag extends Collection implements Arrayable, Htmlable, IteratorAggregate
{
    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function whereStartsWith($string)
    {
        return $this->filter(function ($value, $key) use ($string) {
            return Str::startsWith($key, $string);
        });
    }

    /**
     * Return a bag of attributes with keys that do not start with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function whereDoesntStartWith($string)
    {
        return $this->reject(function ($value, $key) use ($string) {
            return Str::startsWith($key, $string);
        });
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string  $string
     * @return static
     */
    public function thatStartWith($string)
    {
        return $this->whereStartsWith($string);
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function exceptProps($keys)
    {
        $props = [];

        foreach ($keys as $key => $defaultValue) {
            $key = is_numeric($key) ? $defaultValue : $key;

            $props[] = $key;
            $props[] = Str::kebab($key);
        }

        return $this->except($props);
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  mixed  $attributeDefaults
     * @return static
     */
    public function merge($attributeDefaults)
    {
        $attributeDefaults = Collection::make($attributeDefaults)->map(function ($value) {
            return is_object($value) || is_null($value) || is_bool($value)
                ? $value
                : e($value);
        });

        $attributes = $this->map(function ($value, $key) use ($attributeDefaults) {
            return $key === 'class'
                ? implode(' ', array_unique(array_filter([$attributeDefaults->get($key, ''), $value])))
                : $value;
        });

        return new static($attributeDefaults->merge($attributes));
    }

    /**
     * Get all of the raw attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the underlying attributes.
     *
     * @param  mixed  $attributes
     * @return static
     */
    public function setAttributes($attributes = [])
    {
        if (isset($attributes['attributes']) &&
            $attributes['attributes'] instanceof self) {
            $parentBag = $attributes['attributes'];

            unset($attributes['attributes']);

            $attributes = $parentBag->merge($attributes);
        }

        return new static($attributes);
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return (string) $this;
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  mixed  $attributeDefaults
     * @return \Illuminate\Support\HtmlString
     */
    public function __invoke($attributeDefaults = [])
    {
        return new HtmlString((string) $this->merge($attributeDefaults));
    }

    /**
     * Implode the attributes into a single HTML ready string.
     *
     * @return string
     */
    public function __toString()
    {
        return trim($this->filter(function ($value) {
            return ! ($value === false || is_null($value));
        })->map(function ($value, $key) {
            return $key.'="'.str_replace('"', '\\"', trim($value === true ? $key : $value)).'"';
        })->join(' '));
    }
}
