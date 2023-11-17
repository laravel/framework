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
     * Format the current value according to the current locale.
     *
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  ?string  $locale
     * @return string|false
     */
    public function format(?int $precision = null, ?int $maxPrecision = null, ?string $locale = null)
    {
        return Number::format($this->value, $precision, $maxPrecision, $locale);
    }

    /**
     * Convert the current value to its percentage equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  ?string  $locale
     * @return string|false
     */
    public function percentage(int $precision = 0, ?int $maxPrecision = null, ?string $locale = null)
    {
        return Number::percentage($this->value, $precision, $maxPrecision, $locale);
    }

    /**
     * Convert the current value to its currency equivalent.
     *
     * @param  string  $in
     * @param  ?string  $locale
     * @return string|false
     */
    public function currency(string $in = 'USD', ?string $locale = null)
    {
        return Number::currency($this->value, $in, $locale);
    }

    /**
     * Convert the current value to its file size equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public function fileSize(int $precision = 0, ?int $maxPrecision = null)
    {
        return Number::fileSize($this->value, $precision, $maxPrecision);
    }

    /**
     * Convert the current value to its human readable equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public function forHumans(int $precision = 0, ?int $maxPrecision = null)
    {
        return Number::forHumans($this->value, $precision, $maxPrecision);
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
     * Convert the current value to its string equivalent.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
