<?php

namespace Illuminate\Validation\Rules;

use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Stringable;

class Date implements Stringable
{
    use Conditionable, Macroable;

    /**
     * The constraints for the date rule.
     *
     * @var array
     */
    protected $constraints = ['date'];

    /**
     * The format for the date.
     *
     * @var string
     */
    protected $format;

    /**
     * Create a new date rule instance.
     *
     * @param  string  $format
     * @return void
     */
    public function __construct($format = 'Y-m-d')
    {
        $this->format = $format;
    }

    /**
     * Specify the date format to validate against.
     *
     * @param  ?string  $format
     * @return $this
     */
    public function format($format = null)
    {
        return $this->addRule('date_format:'.($format ?? $this->format));
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
     * Ensure the date is after or equal to today.
     *
     * @return $this
     */
    public function afterOrEqualToday()
    {
        return $this->afterOrEqual('today');
    }

    /**
     * Ensure the date is before or equal to today.
     *
     * @return $this
     */
    public function beforeOrEqualToday()
    {
        return $this->beforeOrEqual('today');
    }

    /**
     * Ensure the date is after the specified date.
     *
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $date
     * @return $this
     */
    public function after($date)
    {
        return $this->addRule('after:'.$this->formatDate($date));
    }

    /**
     * Ensure the date is before the specified date.
     *
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $date
     * @return $this
     */
    public function before($date)
    {
        return $this->addRule('before:'.$this->formatDate($date));
    }

    /**
     * Ensure the date is on or after the specified date.
     *
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $date
     * @return $this
     */
    public function afterOrEqual($date)
    {
        return $this->addRule('after_or_equal:'.$this->formatDate($date));
    }

    /**
     * Ensure the date is on or before the specified date.
     *
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $date
     * @return $this
     */
    public function beforeOrEqual($date)
    {
        return $this->addRule('before_or_equal:'.$this->formatDate($date));
    }

    /**
     * Ensure the date is between two dates.
     *
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $from
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $to
     * @return $this
     */
    public function between($from, $to)
    {
        return $this->after($from)->before($to);
    }

    /**
     * Ensure the date is between or equal to two dates.
     *
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $from
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $to
     * @return $this
     */
    public function betweenOrEqual($from, $to)
    {
        return $this->afterOrEqual($from)->beforeOrEqual($to);
    }

    /**
     * Add custom rules to the validation rules array.
     *
     * @param  string|array  $rules
     * @return $this
     */
    public function addRule($rules)
    {
        $this->constraints = array_merge($this->constraints, Arr::wrap($rules));

        return $this;
    }

    /**
     * Format the date for the validation rule.
     *
     * @param  \Illuminate\Support\Carbon|\DateTime|string  $date
     * @return string
     */
    protected function formatDate($date)
    {
        if ($date instanceof Carbon || $date instanceof DateTime) {
            return $date->format($this->format);
        }

        return $date;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return implode('|', $this->constraints);
    }
}
