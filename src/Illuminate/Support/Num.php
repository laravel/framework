<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class Num
{
    use Macroable;

    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param int|float|string $value
     * @return void
     */
    public function __construct($value = 0)
    {
        $this->validateValue($value);
        $this->value = $value;
    }

    /**
     * Get a new num object from the given numeric value.
     *
     * @param int|float|string $value
     * @return static
     */
    public static function of($value)
    {
        return new static($value);
    }

    /**
     * Convert current value to absolute value.
     *
     * @return static
     */
    public function abs()
    {
        $this->value = abs($this->value);

        return $this;
    }

    /**
     * Add value to current value
     *
     * @param int|float|string $value
     * @return static
     */
    public function add($value)
    {
        $this->validateValue($value);

        $this->value += $value;

        return $this;
    }

    /**
     * Divide current value by passed value
     *
     * @param int|float|string $value
     * @return static
     */
    public function divide($value)
    {
        $this->validateValue($value);

        $this->value /= $value;

        return $this;
    }

    /**
     * Format current value
     *
     * @param int $decimals
     * @param string $decimalPoint
     * @param string $thousandsSeparator
     * @return string
     */
    public function format($decimals = 0, $decimalPoint = '.', $thousandsSeparator = ',')
    {
        return number_format($this->value, $decimals, $decimalPoint, $thousandsSeparator);
    }

    /**
     * Check if current value is greater than passed value
     *
     * @param int|float|string $value
     * @return bool
     */
    public function gt($value)
    {
        $this->validateValue($value);

        return $this->value > $value;
    }

    /**
     * Check if current value is greater than or equal to passed value
     *
     * @param int|float|string $value
     * @return bool
     */
    public function gte($value)
    {
        $this->validateValue($value);

        return $this->value >= $value;
    }

    /**
     * Check if current value is float
     *
     * @return bool
     */
    public function isFloat()
    {
        return is_float($this->value);
    }

    /**
     * Check if current value is integer
     *
     * @return bool
     */
    public function isInt()
    {
        return is_int($this->value);
    }

    /**
     * Check if current value is negative
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->value < 0;
    }

    /**
     * Check if current value is numeric
     *
     * @return bool
     */
    public function isNumeric()
    {
        return is_numeric($this->value);
    }

    /**
     * Check if current value is positive
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->value > 0;
    }

    /**
     * Check if current value is zero
     *
     * @return bool
     */
    public function isZero()
    {
        return $this->value == 0;
    }

    /**
     * Natural logarithm
     *
     * @param int|float|null $base
     * @return static
     */
    public function log($base = null)
    {
        $this->value = log($this->value, $base);

        return $this;
    }

    /**
     * Base-10 logarithm
     *
     * @return static
     */
    public function log10()
    {
        $this->value = log10($this->value);

        return $this;
    }

    /**
     * Check if current value is less than passed value
     *
     * @param int|float|string $value
     * @return bool
     */
    public function lt($value)
    {
        $this->validateValue($value);

        return $this->value < $value;
    }

    /**
     * Check if current value is less than or equal to passed value
     *
     * @param int|float|string $value
     * @return bool
     */
    public function lte($value)
    {
        $this->validateValue($value);

        return $this->value <= $value;
    }

    /**
     * Multiply current value by passed value
     *
     * @param int|float|string $value
     * @return static
     */
    public function multiply($value)
    {
        $this->validateValue($value);

        $this->value *= $value;

        return $this;
    }

    /**
     * Exponential current value by passed value
     *
     * @param int|float|string $value
     * @return static
     */
    public function pow($value)
    {
        $this->validateValue($value);

        $this->value **= $value;

        return $this;
    }

    /**
     * Square root of current value
     *
     * @return static
     */
    public function sqrt()
    {
        $this->value = sqrt($this->value);

        return $this;
    }

    /**
     * Subtract value from current value
     *
     * @param int|float|string $value
     * @return static
     */
    public function sub($value)
    {
        $this->validateValue($value);

        $this->value -= $value;

        return $this;
    }

    /**
     * Get the raw float value
     *
     * @return float
     */
    public function toFloat(): float
    {
        return (float)$this->value;
    }

    /**
     * Get the raw int value
     *
     * @return int
     */
    public function toInt()
    {
        return (int)$this->value;
    }

    /**
     * Validate provided value is numeric
     *
     * @param int|float|string $value
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function validateValue($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Invalid value provided. Please provide a integer, float, or numeric string.');
        }
    }
}
