<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use InvalidArgumentException;
use JsonSerializable;

class Numberable implements JsonSerializable
{
    use Conditionable, Dumpable, Macroable, Tappable;

    /**
     * The underlying numeric value.
     *
     * @var int|float
     */
    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param  int|float|string  $value
     */
    public function __construct($value = 0)
    {
        $this->value = is_string($value) ? $this->parseValue($value) : $value;
    }

    /**
     * Parse a string value to int or float.
     *
     * @param  string  $value
     * @return int|float
     */
    protected function parseValue(string $value): int|float
    {
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        throw new InvalidArgumentException("Invalid numeric value: {$value}");
    }

    /**
     * Return the absolute value of the number.
     *
     * @return static
     */
    public function abs()
    {
        return new static(Number::abs($this->value));
    }

    /**
     * Round the number to the specified precision.
     *
     * @param  int  $precision
     * @param  int  $mode
     * @return static
     */
    public function round(int $precision = 0, int $mode = PHP_ROUND_HALF_UP)
    {
        return new static(Number::round($this->value, $precision, $mode));
    }

    /**
     * Round the number up to the nearest integer.
     *
     * @return static
     */
    public function ceil()
    {
        return new static(Number::ceil($this->value));
    }

    /**
     * Round the number down to the nearest integer.
     *
     * @return static
     */
    public function floor()
    {
        return new static(Number::floor($this->value));
    }

    /**
     * Clamp the number between the given minimum and maximum.
     *
     * @param  int|float  $min
     * @param  int|float  $max
     * @return static
     */
    public function clamp(int|float $min, int|float $max)
    {
        return new static(Number::clamp($this->value, $min, $max));
    }

    /**
     * Raise the number to the specified power.
     *
     * @param  int|float  $exponent
     * @return static
     */
    public function power(int|float $exponent)
    {
        return new static(Number::power($this->value, $exponent));
    }

    /**
     * Calculate the square root of the number.
     *
     * @return static
     */
    public function sqrt()
    {
        return new static(Number::sqrt($this->value));
    }

    /**
     * Remove any trailing zero digits after the decimal point.
     *
     * @return static
     */
    public function trim()
    {
        return new static(Number::trim($this->value));
    }

    /**
     * Determine if the number is even.
     *
     * @return bool
     */
    public function isEven(): bool
    {
        return Number::isEven((int) $this->value);
    }

    /**
     * Determine if the number is odd.
     *
     * @return bool
     */
    public function isOdd(): bool
    {
        return Number::isOdd((int) $this->value);
    }

    /**
     * Determine if the number is prime.
     *
     * @return bool
     */
    public function isPrime(): bool
    {
        return Number::isPrime((int) $this->value);
    }

    /**
     * Check if the number is a perfect square.
     *
     * @return bool
     */
    public function isPerfectSquare(): bool
    {
        return Number::isPerfectSquare((int) $this->value);
    }

    /**
     * Calculate the factorial of the number.
     *
     * @return static
     */
    public function factorial()
    {
        return new static(Number::factorial((int) $this->value));
    }

    /**
     * Calculate the greatest common divisor with another number.
     *
     * @param  int  $other
     * @return static
     */
    public function gcd(int $other)
    {
        return new static(Number::gcd((int) $this->value, $other));
    }

    /**
     * Calculate the least common multiple with another number.
     *
     * @param  int  $other
     * @return static
     */
    public function lcm(int $other)
    {
        return new static(Number::lcm((int) $this->value, $other));
    }

    /**
     * Format the number according to the current locale.
     *
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return string|false
     */
    public function format(?int $precision = null, ?int $maxPrecision = null, ?string $locale = null)
    {
        return Number::format($this->value, $precision, $maxPrecision, $locale);
    }

    /**
     * Spell out the number in the given locale.
     *
     * @param  string|null  $locale
     * @param  int|null  $after
     * @param  int|null  $until
     * @return string
     */
    public function spell(?string $locale = null, ?int $after = null, ?int $until = null)
    {
        return Number::spell($this->value, $locale, $after, $until);
    }

    /**
     * Convert the number to ordinal form.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function ordinal(?string $locale = null)
    {
        return Number::ordinal($this->value, $locale);
    }

    /**
     * Spell out the number in ordinal form.
     *
     * @param  string|null  $locale
     * @return string
     */
    public function spellOrdinal(?string $locale = null)
    {
        return Number::spellOrdinal($this->value, $locale);
    }

    /**
     * Convert the number to its percentage equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return string|false
     */
    public function percentage(int $precision = 0, ?int $maxPrecision = null, ?string $locale = null)
    {
        return Number::percentage($this->value, $precision, $maxPrecision, $locale);
    }

    /**
     * Convert the number to its currency equivalent.
     *
     * @param  string  $in
     * @param  string|null  $locale
     * @param  int|null  $precision
     * @return string|false
     */
    public function currency(string $in = '', ?string $locale = null, ?int $precision = null)
    {
        return Number::currency($this->value, $in, $locale, $precision);
    }

    /**
     * Convert the number to its file size equivalent.
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
     * Convert the number to its human-readable equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string|false
     */
    public function forHumans(int $precision = 0, ?int $maxPrecision = null)
    {
        return Number::forHumans($this->value, $precision, $maxPrecision);
    }

    /**
     * Convert the number to its abbreviated equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return bool|string
     */
    public function abbreviate(int $precision = 0, ?int $maxPrecision = null)
    {
        return Number::abbreviate($this->value, $precision, $maxPrecision);
    }

    /**
     * Add another number to this number.
     *
     * @param  int|float  $value
     * @return static
     */
    public function add(int|float $value)
    {
        return new static($this->value + $value);
    }

    /**
     * Subtract another number from this number.
     *
     * @param  int|float  $value
     * @return static
     */
    public function subtract(int|float $value)
    {
        return new static($this->value - $value);
    }

    /**
     * Multiply this number by another number.
     *
     * @param  int|float  $value
     * @return static
     */
    public function multiply(int|float $value)
    {
        return new static($this->value * $value);
    }

    /**
     * Divide this number by another number.
     *
     * @param  int|float  $value
     * @return static
     */
    public function divide(int|float $value)
    {
        return new static($this->value / $value);
    }

    /**
     * Get the modulus of this number.
     *
     * @param  int|float  $value
     * @return static
     */
    public function modulus(int|float $value)
    {
        return new static($this->value % $value);
    }

    /**
     * Check if this number is equal to another number.
     *
     * @param  int|float  $value
     * @return bool
     */
    public function equals(int|float $value): bool
    {
        return $this->value === $value;
    }

    /**
     * Check if this number is greater than another number.
     *
     * @param  int|float  $value
     * @return bool
     */
    public function greaterThan(int|float $value): bool
    {
        return $this->value > $value;
    }

    /**
     * Check if this number is greater than or equal to another number.
     *
     * @param  int|float  $value
     * @return bool
     */
    public function greaterThanOrEqual(int|float $value): bool
    {
        return $this->value >= $value;
    }

    /**
     * Check if this number is less than another number.
     *
     * @param  int|float  $value
     * @return bool
     */
    public function lessThan(int|float $value): bool
    {
        return $this->value < $value;
    }

    /**
     * Check if this number is less than or equal to another number.
     *
     * @param  int|float  $value
     * @return bool
     */
    public function lessThanOrEqual(int|float $value): bool
    {
        return $this->value <= $value;
    }

    /**
     * Check if this number is between two other numbers.
     *
     * @param  int|float  $min
     * @param  int|float  $max
     * @return bool
     */
    public function between(int|float $min, int|float $max): bool
    {
        return $this->value >= $min && $this->value <= $max;
    }

    /**
     * Get the underlying numeric value.
     *
     * @return int|float
     */
    public function value(): int|float
    {
        return $this->value;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return int|float
     */
    public function jsonSerialize(): int|float
    {
        return $this->value;
    }

    /**
     * Convert the number to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
