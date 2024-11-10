<?php

namespace Illuminate\Validation\Rules;


use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Stringable;

class Date implements Stringable
{
    use Conditionable, Macroable;

    /**
     * Array of validation rules.
     *
     * @var array
     */
    protected $rules = ['date'];

    /**
     * Specify the date format to validate against.
     *
     * @param  string  $format
     * @return $this
     */
    public function format($format = 'Y-m-d')
    {
        return $this->addRule("date_format:$format");
    }

    /**
     * Ensure the date is after today.
     *
     * @return $this
     */
    public function afterToday()
    {
        return $this->after('today');
    }

    /**
     * Ensure the date is before today.
     *
     * @return $this
     */
    public function beforeToday()
    {
        return $this->before('today');
    }

    /**
     * Ensure the date is after the specified date.
     *
     * @param  string  $date
     * @return $this
     */
    public function after($date)
    {
        return $this->addRule("after:$date");
    }

    /**
     * Ensure the date is before the specified date.
     *
     * @param  string  $date
     * @return $this
     */
    public function before($date)
    {
        return $this->addRule("before:$date");
    }

    /**
     * Ensure the date is on or after the specified date.
     *
     * @param  string  $date
     * @return $this
     */
    public function afterOrEqual($date)
    {
        return $this->addRule("after_or_equal:$date");
    }

    /**
     * Ensure the date is on or before the specified date.
     *
     * @param  string  $date
     * @return $this
     */
    public function beforeOrEqual($date)
    {
        return $this->addRule("before_or_equal:$date");
    }

    /**
     * Add custom rules to the validation rules array.
     *
     * @param  string|array  $rules
     * @return $this
     */
    public function addRule($rules)
    {
        $this->rules = array_merge($this->rules, Arr::wrap($rules));

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return implode(',', $this->rules);
    }
}
