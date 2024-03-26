<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Macroable;
use NumberFormatter;
use Random\Randomizer;
use RuntimeException;

class Number
{
    use Macroable;

    /**
     * The current default locale.
     *
     * @var string
     */
    protected static $locale = 'en';

    /**
     * Get a new numeral instance for the given value.
     *
     * @param  int|float  $value
     */
    public static function of($value): Numeral
    {
        return new Numeral($value);
    }

    /**
     * Determine if the given value is a number.
     *
     * @param  mixed  $number
     */
    public static function isNumeric($number): bool
    {
        return is_numeric($number);
    }

    /**
     * Determine if the given value is an even number.
     *
     * @param  mixed  $value
     */
    public static function isEven($value): bool
    {
        return $value % 2 === 0;
    }

    /**
     * Determine if the given value is an odd number.
     *
     * @param  mixed  $value
     */
    public static function isOdd($value): bool
    {
        return $value % 2 !== 0;
    }

    /**
     * Determine if the given value is a float.
     *
     * @param  mixed  $value
     */
    public static function isFloat($value): bool
    {
        return self::isNumeric($value) && is_float($value + 0);
    }

    /**
     * Determine if the given value is an integer.
     *
     * @param  mixed  $value
     */
    public static function isInt($value): bool
    {
        return self::isNumeric($value) && is_int($value + 0);
    }

    /**
     * Determine if the given value is a positive number.
     *
     * @param  mixed  $value
     */
    public static function isPositive($value): bool
    {
        return $value > 0;
    }

    /**
     * Determine if the given value is a positive integer.
     *
     * @param  mixed  $value
     */
    public static function isPositiveInt($value): bool
    {
        return self::isInt($value) && self::isPositive($value);
    }

    /**
     * Determine if the given value is a positive float.
     *
     * @param  mixed  $value
     */
    public static function isPositiveFloat($value): bool
    {
        return self::isFloat($value) && self::isPositive($value);
    }

    /**
     * Determine if the given value is a negative number.
     *
     * @param  mixed  $value
     */
    public static function isNegative($value): bool
    {
        return $value < 0;
    }

    /**
     * Determine if the given value is a negative integer.
     *
     * @param  mixed  $value
     */
    public static function isNegativeInt($value): bool
    {
        return self::isInt($value) && self::isNegative($value);
    }

    /**
     * Determine if the given value is a negative float.
     *
     * @param  mixed  $value
     */
    public static function isNegativeFloat($value): bool
    {
        return self::isFloat($value) && self::isNegative($value);
    }

    /**
     * Determine if the given value is zero.
     *
     * @param  mixed  $value
     */
    public static function isZero($value): bool
    {
        return (int) $value === 0;
    }

    /**
     * Format the given number according to the current locale.
     *
     * @param  int|float  $number
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return string|false
     */
    public static function format(int|float $number, ?int $precision = null, ?int $maxPrecision = null, ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::DECIMAL);

        if (! is_null($maxPrecision)) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxPrecision);
        } elseif (! is_null($precision)) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        }

        return $formatter->format($number);
    }

    /**
     * Spell out the given number in the given locale.
     *
     * @param  int|float  $number
     * @param  string|null  $locale
     * @param  int|null  $after
     * @param  int|null  $until
     * @return string
     */
    public static function spell(int|float $number, ?string $locale = null, ?int $after = null, ?int $until = null)
    {
        static::ensureIntlExtensionIsInstalled();

        if (! is_null($after) && $number <= $after) {
            return static::format($number, locale: $locale);
        }

        if (! is_null($until) && $number >= $until) {
            return static::format($number, locale: $locale);
        }

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::SPELLOUT);

        return $formatter->format($number);
    }

    /**
     * Convert the given number to ordinal form.
     *
     * @param  int|float  $number
     * @param  string|null  $locale
     * @return string
     */
    public static function ordinal(int|float $number, ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::ORDINAL);

        return $formatter->format($number);
    }

    /**
     * Convert the given number to its percentage equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return string|false
     */
    public static function percentage(int|float $number, int $precision = 0, ?int $maxPrecision = null, ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::PERCENT);

        if (! is_null($maxPrecision)) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxPrecision);
        } else {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        }

        return $formatter->format($number / 100);
    }

    /**
     * Convert the given number to its currency equivalent.
     *
     * @param  int|float  $number
     * @param  string  $in
     * @param  string|null  $locale
     * @return string|false
     */
    public static function currency(int|float $number, string $in = 'USD', ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($number, $in);
    }

    /**
     * Convert the given number to its file size equivalent.
     *
     * @param  int|float  $bytes
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public static function fileSize(int|float $bytes, int $precision = 0, ?int $maxPrecision = null)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', static::format($bytes, $precision, $maxPrecision), $units[$i]);
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return bool|string
     */
    public static function abbreviate(int|float $number, int $precision = 0, ?int $maxPrecision = null)
    {
        return static::forHumans($number, $precision, $maxPrecision, abbreviate: true);
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  bool  $abbreviate
     * @return bool|string
     */
    public static function forHumans(int|float $number, int $precision = 0, ?int $maxPrecision = null, bool $abbreviate = false)
    {
        return static::summarize($number, $precision, $maxPrecision, $abbreviate ? [
            3 => 'K',
            6 => 'M',
            9 => 'B',
            12 => 'T',
            15 => 'Q',
        ] : [
            3 => ' thousand',
            6 => ' million',
            9 => ' billion',
            12 => ' trillion',
            15 => ' quadrillion',
        ]);
    }

    /**
     * Convert the number to its human-readable equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  array  $units
     * @return string|false
     */
    protected static function summarize(int|float $number, int $precision = 0, ?int $maxPrecision = null, array $units = [])
    {
        if (empty($units)) {
            $units = [
                3 => 'K',
                6 => 'M',
                9 => 'B',
                12 => 'T',
                15 => 'Q',
            ];
        }

        switch (true) {
            case floatval($number) === 0.0:
                return $precision > 0 ? static::format(0, $precision, $maxPrecision) : '0';
            case $number < 0:
                return sprintf('-%s', static::summarize(abs($number), $precision, $maxPrecision, $units));
            case $number >= 1e15:
                return sprintf('%s'.end($units), static::summarize($number / 1e15, $precision, $maxPrecision, $units));
        }

        $numberExponent = floor(log10($number));
        $displayExponent = $numberExponent - ($numberExponent % 3);
        $number /= pow(10, $displayExponent);

        return trim(sprintf('%s%s', static::format($number, $precision, $maxPrecision), $units[$displayExponent] ?? ''));
    }

    /**
     * Clamp the given number between the given minimum and maximum.
     *
     * @param  int|float  $number
     * @param  int|float  $min
     * @param  int|float  $max
     * @return int|float
     */
    public static function clamp(int|float $number, int|float $min, int|float $max)
    {
        return min(max($number, $min), $max);
    }

    /**
     * Counts the length of a numeral
     *
     * @param $value
     * @return int|null
     */
    public static function len($value): ?int
    {
        if(self::isNumeric($value) === false) {
            return null;
        }

        return mb_strlen($value);
    }

    /**
     * Returns a random number using the Randomizer class from PHP
     */
    public static function random(): Numeral
    {
        return new Numeral((new Randomizer())->nextInt());
    }

    /**
     * Returns a random number between the given min and max values
     */
    public static function randomBetween(int $min, int $max): Numeral
    {
        return new Numeral((new Randomizer())->getInt($min, $max));
    }

    /**
     * Get the greatest common divisor of two numbers.
     */
    public static function gcd(int|float $a, int|float $b): int|float
    {
        if (self::isZero($b)) {
            return $a;
        }

        if (self::isZero($a)) {
            return $b;
        }

        return static::gcd($b, $a % $b);
    }

    /**
     * Get the least common multiple of two numbers.
     */
    public static function lcm(int|float $a, int|float $b): int|float
    {
        return self::isZero($a) || self::isZero($b)
            ? 0
            : abs($a * $b) / static::gcd($a, $b);
    }

    /**
     * Get the factorial of a number.
     */
    public static function factorial(int|float $number): int|float
    {
        if ($number < 0) {
            return 0;
        }

        if ($number === 0) {
            return 1;
        }

        return $number * static::factorial($number - 1);
    }

    /**
     * Execute the given callback using the given locale.
     *
     * @param  string  $locale
     * @param  callable  $callback
     * @return mixed
     */
    public static function withLocale(string $locale, callable $callback)
    {
        $previousLocale = static::$locale;

        static::useLocale($locale);

        return tap($callback(), fn () => static::useLocale($previousLocale));
    }

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public static function useLocale(string $locale)
    {
        static::$locale = $locale;
    }

    /**
     * Ensure the "intl" PHP extension is installed.
     *
     * @return void
     */
    protected static function ensureIntlExtensionIsInstalled()
    {
        if (! extension_loaded('intl')) {
            $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

            throw new RuntimeException('The "intl" PHP extension is required to use the ['.$method.'] method.');
        }
    }
}
