<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use InvalidArgumentException;
use Stringable as BaseStringable;

class Numeral implements BaseStringable
{
    use Conditionable, Dumpable, Macroable, Tappable;

    protected $value;

    /**
     * Create a new numeral instance.
     *
     * @param $value
     */
    public function __construct($value = 0)
    {
        $this->value = $this->getNumeralValue($value);
    }

    /**
     * Determine if the numeral is an even number.
     */
    public function isEven(): bool
    {
        return Number::isEven($this->value);
    }

    /**
     * Determine if the numeral is an odd number.
     */
    public function isOdd(): bool
    {
        return Number::isOdd($this->value);
    }

    /**
     * Determine if the numeral is a float.
     */
    public function isFloat(): bool
    {
        return Number::isFloat($this->value);
    }

    /**
     * Determine if the numeral is an integer.
     */
    public function isInt(): bool
    {
        return Number::isInt($this->value);
    }

    /**
     * Determine if the numeral is a positive number.
     */
    public function isPositive(): bool
    {
        return Number::isPositive($this->value);
    }

    /**
     * Determine if the numeral is a positive integer.
     */
    public function isPositiveInt(): bool
    {
        return Number::isPositiveInt($this->value);
    }

    /**
     * Determine if the numeral is a positive float.
     */
    public function isPositiveFloat(): bool
    {
        return Number::isPositiveFloat($this->value);
    }

    /**
     * Determine if the numeral is a negative number.
     */
    public function isNegative(): bool
    {
        return Number::isNegative($this->value);
    }

    /**
     * Determine if the numeral is a negative integer.
     */
    public function isNegativeInt(): bool
    {
        return Number::isNegativeInt($this->value);
    }

    /**
     * Determine if the numeral is a negative float.
     */
    public function isNegativeFloat(): bool
    {
        return Number::isNegativeFloat($this->value);
    }

    /**
     * Determine if the numeral is zero.
     */
    public function isZero(): bool
    {
        return Number::isZero($this->value);
    }

    /**
     * Inverts the sign of the number.
     */
    public function negate(): static
    {
        return new static(-$this->value);
    }

    /**
     * Format the given number according to the current locale.
     *
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return Stringable|false
     */
    public function format(?int $precision = null, ?int $maxPrecision = null, ?string $locale = null)
    {
        $result = Number::format($this->value, $precision, $maxPrecision, $locale);

        return $result !== false ? str($result) : false;
    }

    /**
     * Spell out the given number in the given locale.
     *
     * @param  string|null  $locale
     * @param  int|null  $after
     * @param  int|null  $until
     * @return Stringable
     */
    public function spell(?string $locale = null, ?int $after = null, ?int $until = null)
    {
        return str(Number::spell($this->value, $locale, $after, $until));
    }

    /**
     * Convert the given number to ordinal form.
     *
     * @param  string|null  $locale
     * @return Stringable
     */
    public function ordinal(?string $locale = null)
    {
        return str(Number::ordinal($this->value, $locale));
    }

    /**
     * Convert the given number to its percentage equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return Stringable|false
     */
    public function percentage(int $precision = 0, ?int $maxPrecision = null, ?string $locale = null)
    {
        $result = Number::percentage($this->value, $precision, $maxPrecision, $locale);

        return $result ? str($result) : false;
    }

    /**
     * Convert the given number to its currency equivalent.
     *
     * @param  string  $in
     * @param  string|null  $locale
     * @return Stringable|false
     */
    public function currency(string $in = 'USD', ?string $locale = null)
    {
        $result = Number::currency($this->value, $in, $locale);

        return $result ? str($result) : false;
    }

    /**
     * Convert the given number to its file size equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return Stringable
     */
    public function fileSize(int $precision = 0, ?int $maxPrecision = null)
    {
        return str(Number::fileSize($this->value, $precision, $maxPrecision));
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return bool|Stringable
     */
    public function abbreviate(int $precision = 0, ?int $maxPrecision = null)
    {
        $result = Number::abbreviate($this->value, $precision, $maxPrecision);

        return $result !== false ? str($result) : false;
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  bool  $abbreviate
     * @return bool|Stringable
     */
    public function forHumans(int $precision = 0, ?int $maxPrecision = null, bool $abbreviate = false)
    {
        $result = Number::forHumans($this->value, $precision, $maxPrecision, $abbreviate);

        return $result !== false ? str($result) : false;
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  array  $units
     * @return Stringable|false
     */
    protected function summarize(int|float $number, int $precision = 0, ?int $maxPrecision = null, array $units = [])
    {
        $result = Number::summarize($number, $precision, $maxPrecision, $units);

        return $result !== false ? str($result) : false;
    }

    /**
     * Returns a new instance of self with the max between the current value and the given value.
     */
    public function max(int|float|self $value): self
    {
        return new static(max($this->value, $this->getNumeralValue($value)));
    }

    /**
     * Returns a new instance of self with the min between the current value and the given value.
     */
    public function min(int|float|self $value): self
    {
        return new static(min($this->value, $this->getNumeralValue($value)));
    }

    /**
     * Returns a new instance of self with the value clamped between the given min and max.
     */
    public function clamp(int|float|self $min, int|float|self $max): self
    {
        return $this->min($max)->max($min);
    }

    /**
     * Returns a new instance of self with the value of all given items summed up to the value of the instance.
     */
    public function sum(int|float|self ...$values): self
    {
        return new static(collect($values)
            ->map(fn ($value) => $this->getNumeralValue($value))
            ->merge([$this->value])
            ->sum());
    }

    /**
     * Returns a new instance of self with the value of the given item subtracted from the value of the instance.
     */
    public function subtract(int|float|self $value): static
    {
        return new static($this->value - $this->getNumeralValue($value));
    }

    /**
     * Returns a new instance of self with the value of the instance multiplied by the given value.
     */
    public function multiply(int|float|self $value): static
    {
        return new static($this->value * $this->getNumeralValue($value));
    }

    /**
     * Returns a new instance of self with the value of the instance divided by the given value.
     */
    public function divide(int|float|self $value): static
    {
        return new static($this->value / $this->getNumeralValue($value));
    }

    /**
     * Determines if the given numeral is equal to the instance.
     */
    public function equals(int|float|self $value): bool
    {
        return $this->value === $this->getNumeralValue($value);
    }

    /**
     * Determines if the given numeral is not equal than the instance.
     */
    public function notEquals(int|float|self $value): bool
    {
        return $this->value !== $this->getNumeralValue($value);
    }

    /**
     * Determines if the given numeral is close to the instance.
     */
    public function nearlyEquals(int|float|self $value, int $precision = 6): bool
    {
        return round($this->value, $precision) === round($this->getNumeralValue($value), $precision);
    }

    /**
     * Determines if the given numeral is greater than the instance.
     */
    public function greaterThan(int|float|self $value): bool
    {
        return $this->value > $this->getNumeralValue($value);
    }

    /**
     * Determines if the given numeral is greater than or equal to the instance.
     */
    public function greaterThanOrEquals(int|float|self $value): bool
    {
        return $this->value >= $this->getNumeralValue($value);
    }

    /**
     * Determines if the given numeral is less than the instance.
     */
    public function lessThan(int|float|self $value): bool
    {
        return $this->value < $this->getNumeralValue($value);
    }

    /**
     * Determines if the given numeral is less than or equal to the instance.
     */
    public function lessThanOrEquals(int|float|self $value): bool
    {
        return $this->value <= $this->getNumeralValue($value);
    }

    /**
     * Determines if the instance is between the given min and max values.
     */
    public function between(int|float|self $min, int|float|self $max): bool
    {
        return $this->greaterThanOrEquals($this->getNumeralValue($min))
            && $this->lessThanOrEquals($this->getNumeralValue($max));
    }

    /**
     * Increment the value of the numeral by the given step.
     */
    public function increment(int|float|self $step = 1): static
    {
        return new static($this->value + $this->getNumeralValue($step));
    }

    /**
     * Decrement the value of the numeral by the given step.
     */
    public function decrement(int|float|self $step = 1): static
    {
        return new static($this->value - $this->getNumeralValue($step));
    }

    /**
     * Get the absolute value of the numeral.
     */
    public function abs(): static
    {
        return new static(abs($this->value));
    }

    /**
     * Get the ceiling value of the numeral.
     */
    public function ceil(): static
    {
        return new static(ceil($this->value));
    }

    /**
     * Get the floor value of the numeral.
     */
    public function floor(): static
    {
        return new static(floor($this->value));
    }

    /**
     * Get the rounded value of the numeral.
     */
    public function round(int $precision = 0): static
    {
        return new static(round($this->value, $precision));
    }

    /**
     * Count the length of the numeral.
     */
    public function len(): ?int
    {
        return Number::len($this->value);
    }

    /**
     * Returns the square root of the numeral.
     */
    public function sqrt(): static
    {
        return new static(sqrt($this->value));
    }

    /**
     * Returns the cube root of the numeral.
     */
    public function cbrt(): self
    {
        return new static($this->value ** (1/3));
    }

    /**
     * Returns the value of the numeral raised to the given exponent.
     */
    public function pow(int|float $exponent): static
    {
        return new static($this->value ** $exponent);
    }

    /**
     * Returns the modulus of the numeral.
     */
    public function mod(int|float $modulus): static
    {
        return new static($this->value % $modulus);
    }

    /**
     * Returns the value of the numeral as an integer.
     */
    public function toInt(): static
    {
        return new static((int) $this->value);
    }

    /**
     * Returns the logarithm of the numeral.
     */
    public function log(): static
    {
        return new static(log($this->value));
    }

    /**
     * Returns the base 10 logarithm of the numeral.
     */
    public function log10(): static
    {
        return new static(log10($this->value));
    }

    /**
     * Returns the natural logarithm of the numeral.
     */
    public function log1p(): static
    {
        return new static(log1p($this->value));
    }

    /**
     * Returns the exponential value of the numeral.
     */
    public function exp(): static
    {
        return new static(exp($this->value));
    }

    /**
     * Returns the exponential minus 1 value of the numeral.
     */
    public function expm1(): static
    {
        return new static(expm1($this->value));
    }

    /**
     * Returns the cosine of the numeral.
     */
    public function cos(): static
    {
        return new static(cos($this->value));
    }

    /**
     * Returns the sine of the numeral.
     */
    public function sin(): static
    {
        return new static(sin($this->value));
    }

    /**
     * Returns the tangent of the numeral.
     */
    public function tan(): static
    {
        return new static(tan($this->value));
    }

    /**
     * Returns the arc cosine of the numeral.
     */
    public function acos(): static
    {
        return new static(acos($this->value));
    }

    /**
     * Returns the arc sine of the numeral.
     */
    public function asin(): static
    {
        return new static(asin($this->value));
    }

    /**
     * Returns the arc tangent of the numeral.
     */
    public function atan(): static
    {
        return new static(atan($this->value));
    }

    /**
     * Returns the hyperbolic cosine of the numeral.
     */
    public function cosh(): static
    {
        return new static(cosh($this->value));
    }

    /**
     * Returns the hyperbolic sine of the numeral.
     */
    public function sinh(): static
    {
        return new static(sinh($this->value));
    }

    /**
     * Returns the hyperbolic tangent of the numeral.
     */
    public function tanh(): static
    {
        return new static(tanh($this->value));
    }

    /**
     * Returns the inverse hyperbolic cosine of the numeral.
     */
    public function acosh(): static
    {
        return new static(acosh($this->value));
    }

    /**
     * Returns the inverse hyperbolic sine of the numeral.
     */
    public function asinh(): static
    {
        return new static(asinh($this->value));
    }

    /**
     * Returns the inverse hyperbolic tangent of the numeral.
     */
    public function atanh(): static
    {
        return new static(atanh($this->value));
    }

    /**
     * Returns the arc tangent of the numeral and the given value.
     */
    public function atan2(int|float|self $value): static
    {
        return new static(atan2($this->value, $this->getNumeralValue($value)));
    }

    /**
     * Converts the numeral to the specified base.
     */
    public function toBase(int $to, int $from = 10): static
    {
        return new static(base_convert($this->value, $from, $to));
    }

    /**
     * Converts the numeral to binary.
     */
    public function toBinary(): static
    {
        return $this->toBase(2);
    }

    /**
     * Converts the numeral to octal.
     */
    public function toOctal(): static
    {
        return $this->toBase(8);
    }

    /**
     * Converts the numeral to hexadecimal.
     */
    public function toHex(): static
    {
        return $this->toBase(16);
    }

    /**
     * Returns the greatest common divisor of the numeral and the given value.
     */
    public function gcd(int|float|self $value): static
    {
        return new static(Number::gcd($this->value, $this->getNumeralValue($value)));
    }

    /**
     * Returns the least common multiple of the numeral and the given value.
     */
    public function lcm(int|float|self $value): static
    {
        return new static(Number::lcm($this->value, $this->getNumeralValue($value)));
    }

    /**
     * Returns the factorial of the numeral.
     */
    public function factorial(): static
    {
        return new static(Number::factorial($this->value));
    }

    /**
     * Returns the numeral with the sign of the given numeral.
     */
    public function copySign(int|float|self $from): static
    {
        if ($this->isNegative()) {
            if (Number::isPositive($from)) {
                return new static($this->negate());
            }

            return new static($this->value);
        }

        return new static($this->value * (Number::of($from)->isNegative() ? -1 : 1));
    }

    /**
     * Get a new numeral instance for the given value.
     *
     * @param  mixed  $value
     */
    protected function getNumeralValue($value): int|float
    {
        if ($value instanceof self) {
            return $value->value();
        }

        if (Number::isNumeric($value)) {
            return $value;
        }

        if ($value === null) {
            return 0;
        }

        throw new InvalidArgumentException('Numeral requires a number');
    }

    /**
     * Dump the numeral.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function dump(...$args)
    {
        dump($this->value, ...$args);

        return $this;
    }

    /**
     * Get the underlying value of the numeral.
     *
     * @return int|float
     */
    public function value(): int|float
    {
        return $this->value;
    }

    /**
     * Get the raw value of the numeral as a string.
     */
    public function toString(): string
    {
        return (string) $this->format();
    }

    /**
     * Get the raw value of the numeral as a string.
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
