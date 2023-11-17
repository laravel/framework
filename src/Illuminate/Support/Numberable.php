<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

class Numberable
{
    use Conditionable, Macroable, Tappable;

    /**
     * The underlying numeric value.
     *
     * @var int|float
     */
    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param  int|float  $value
     * @return void
     */
    public function __construct($value = 0)
    {
        $this->value = $value;
    }

    /**
     * Get the raw numeric value.
     *
     * @return int|float
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Add the given value to the current value.
     *
     * @param  int|float  $value
     */
    public function add($value): static
    {
        $this->value += $value;

        return $this;
    }

    /**
     * Subtract the given value from the current value.
     *
     * @param  int|float  $value
     */
    public function subtract($value): static
    {
        $this->value -= $value;

        return $this;
    }

    /**
     * Multiply the given value with the current value.
     *
     * @param  int|float  $value
     */
    public function multiply($value): static
    {
        $this->value *= $value;

        return $this;
    }

    /**
     * Divide the given value with the current value.
     *
     * @param  int|float  $value
     */
    public function divide($value): static
    {
        $this->value /= $value;

        return $this;
    }

    /**
     * Modulo the given value with the current value.
     *
     * @param  int|float  $value
     */
    public function modulo($value): static
    {
        $this->value %= $value;

        return $this;
    }

    /**
     * Raise the current value to the given exponent.
     *
     * @param  int|float  $value
     */
    public function pow($value): static
    {
        $this->value **= $value;

        return $this;
    }

    /**
     * Proxy dynamic properties onto methods.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->{$key}();
    }

    /**
     * Get the raw string value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
