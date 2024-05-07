<?php

namespace Illuminate\Validation\Rules;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use UnitEnum;

trait ArrayableRule
{
    /**
     * The accepted values.
     *
     * @var array
     */
    protected $values;

    /**
     * The accepted appends.
     *
     * @var array|string
     */
    protected $appends = [];

    /**
     * The accepted removals.
     *
     * @var array|string
     */
    protected $removals = [];

    /**
     * Create a new rule instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\BackedEnum|\UnitEnum|array|string  $values
     * @return void
     */
    public function __construct($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $this->values = is_array($values) ? $values : func_get_args();
    }

    /**
     * Append kays to the values.
     *
     * @param  array|string  $values
     * @return $this
     */
    public function append($values)
    {
        $this->appends = is_array($values) ? $values : func_get_args();

        return $this;
    }

    /**
     * Remove kays from the values.
     *
     * @param  array|string  $values
     * @return $this
     */
    public function remove($values)
    {
        $this->removals = is_array($values) ? $values : func_get_args();

        return $this;
    }

    /**
     * Format the array values.
     * 
     * @param  array  $values
     * @return array
     */
    protected function formatArray($values)
    {
        return array_map(function ($value) {
            $value = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof UnitEnum => $value->name,
                default => $value,
            };

            return '"' . str_replace('"', '""', $value) . '"';
        }, $values);
    }

    /**
     * Format the values into an array.
     *
     * @return array
     */
    protected function formatValues()
    {
        return array_values(array_filter(
            array_diff(
                array_unique(array_merge($this->formatArray($this->values), $this->formatArray($this->appends))),
                $this->formatArray($this->removals)
            ),
        ));
    }
}
