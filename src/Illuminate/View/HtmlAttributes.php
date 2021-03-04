<?php

namespace Illuminate\View;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class HtmlAttributes
{
    /**
     * The raw array of attributes or attribute name.
     *
     * @var mixed|array|string
     */
    protected $attributes;

    /**
     * Constraints for attribute items.
     *
     * @var array|bool|null
     */
    protected $constraints;

    /**
     * Set if the specific attribute value should be escaped.
     *
     * @var bool
     */
    protected $escape;

    /**
     * Create a new HtmlAttributes instance.
     *
     * @param mixed|string|array $attributes
     * @param array|bool|null    $constraints
     * @param bool               $escape
     * @return void
     */
    public function __construct($attributes, $constraints = null, $escape = true)
    {
        $this->attributes = $attributes;
        $this->constraints = $constraints;
        $this->escape = $escape;
    }

    /**
     * Conditionally make element-specific custom attribute.
     *
     * @return array
     */
    public function make()
    {
        $attributes = $this->attributes;

        if (! is_array($attributes)) {
            $attributes = [$attributes => $this->constraints];
        }

        [$numericList, $assocList] = collect($attributes)
            ->partition(function ($value, $key) {
                return is_int($key) && is_string($value);
            });

        $attributes = $assocList->merge($numericList->mapWithKeys(function ($value) {
            return [$value => true];
        }));

        $attributes = $attributes->map(function ($value, $key) {
            $filterAttribute = $this->filterCustomAttribute($key, $value);

            if (! $filterAttribute) {
                return false;
            }

            [$key, $value] = $this->filterCustomAttribute($key, $value);

            if (blank($value)) {
                return false;
            }

            return $this->attributeConstraints($key, $value, false);
        })->reject(function ($value) {
            return $value === false || blank($value);
        })->all();

        return $this->mergeConditionallyAttributes($attributes);
    }

    /**
     * Conditionally merge element-specific custom attributes.
     *
     * @param string      $attribute
     * @param mixed|array $attributeList
     * @param bool        $merge
     * @return static|string
     */
    private function attributeConstraints(string $attribute, $attributeList, bool $merge = false)
    {
        $attributeList = Arr::wrap($attributeList);

        $attributes = [];

        foreach ($attributeList as $key => $constraint) {
            if (is_numeric($key)) {
                $attributes[] = $constraint;

                if ($attribute !== 'class') {
                    break;
                }
            } elseif ($constraint) {
                $attributes[] = $key;

                if ($attribute !== 'class') {
                    break;
                }
            }
        }

        return implode(' ', $attributes);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return mixed
     */
    private function filterCustomAttribute($key, $value)
    {
        if ($value === false
            || (is_string($key) && blank($value))
            || blank($value)) {
            return false;
        }

        if (is_bool($value) && $value) {
            return [$key, $key];
        }
        if (is_int($key) && ! blank($value)) {
            return [$value, $value];
        }

        return [$key, $value];
    }

    /**
     * Merge additional element-specific attributes / values.
     *
     * @param  array  $attributes
     * @return array
     */
    private function mergeConditionallyAttributes(array $attributes = [])
    {
        $attributes = array_map(function ($value) {
            return $this->shouldEscapeAttributeValue($value)
                ? e($value)
                : $value;
        }, $attributes);

        [$appendableAttributes, $nonAppendableAttributes] = collect($attributes)
            ->partition(function ($value, $key) {
                return $key === 'class' || $value instanceof AppendableAttributeValue;
            });

        $newAttributes = $appendableAttributes->mapWithKeys(function ($value, $key) use ($attributes) {
            $defaultsValue = $value instanceof AppendableAttributeValue
                ? $this->resolveAppendableAttributeDefault($attributes, $key)
                : ($value ?? '');

            return [$key => implode(' ', array_unique(array_filter([$defaultsValue, $value])))];
        })->merge($nonAppendableAttributes)->all();

        return array_merge($attributes, $newAttributes);
    }

    /**
     * Determine if the specific attribute value should be escaped.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function shouldEscapeAttributeValue($value)
    {
        if (! $this->escape) {
            return false;
        }

        return ! is_object($value) &&
               ! is_null($value) &&
               ! is_bool($value);
    }

    /**
     * Resolve an appendable attribute value default value.
     *
     * @param array  $attributes
     * @param string $key
     * @return mixed
     */
    protected function resolveAppendableAttributeDefault($attributes, $key)
    {
        if ($this->shouldEscapeAttributeValue($value = $attributes[$key]->value)) {
            $value = e($value);
        }

        return $value;
    }

    /**
     * Get content as a string of HTML.
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function toHtml()
    {
        return new HtmlString((string) $this);
    }

    /**
     * Implode the attributes into a single HTML ready string.
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';

        $attributes = $this->make();
        foreach ($attributes as $key => $value) {
            if ($value === false || is_null($value)) {
                continue;
            }

            if ($value === true) {
                $value = $key;
            }

            $string .= ' '.$key.'="'.str_replace('"', '\\"', trim($value)).'"';
        }

        return trim($string);
    }
}
