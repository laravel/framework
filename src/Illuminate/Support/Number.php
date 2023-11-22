<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Macroable;
use NumberFormatter;
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
     * Format the given number according to the current locale.
     *
     * @param  int|float  $number
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  ?string  $locale
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
     * @param  ?string  $locale
     * @return string
     */
    public static function spell(int|float $number, ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::SPELLOUT);

        return $formatter->format($number);
    }

    /**
     * Convert the given number to ordinal form.
     *
     * @param  int|float  $number
     * @param  ?string  $locale
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
     * @param  ?string  $locale
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
     * @param  ?string  $locale
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
     * Convert the number to its human readable equivalent.
     *
     * @param  int  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public static function forHumans(int|float $number, int $precision = 0, ?int $maxPrecision = null)
    {
        $units = [
            3 => 'thousand',
            6 => 'million',
            9 => 'billion',
            12 => 'trillion',
            15 => 'quadrillion',
        ];

        switch (true) {
            case $number === 0:
                return '0';
            case $number < 0:
                return sprintf('-%s', static::forHumans(abs($number), $precision, $maxPrecision));
            case $number >= 1e15:
                return sprintf('%s quadrillion', static::forHumans($number / 1e15, $precision, $maxPrecision));
        }

        $numberExponent = floor(log10($number));
        $displayExponent = $numberExponent - ($numberExponent % 3);
        $number /= pow(10, $displayExponent);

        return trim(sprintf('%s %s', static::format($number, $precision, $maxPrecision), $units[$displayExponent] ?? ''));
    }

    /**
     * Convert the given time units to microseconds.
     *
     * @param  int|float  $milliseconds
     * @param  int|float  $seconds
     * @param  int|float  $minutes
     * @param  int|float  $hours
     * @param  int|float  $days
     * @param  int|float  $weeks
     * @param  int|float  $years
     * @return float|int
     */
    public static function microseconds(
        int|float $milliseconds = 0,
        int|float $seconds = 0,
        int|float $minutes = 0,
        int|float $hours = 0,
        int|float $days = 0,
        int|float $weeks = 0,
        int|float $years = 0
    ) {
        $milliseconds += static::milliseconds($seconds, $minutes, $hours, $days, $weeks, $years);

        return $milliseconds * 1000;
    }

    /**
     * Convert the given time units to milliseconds.
     *
     * @param  int|float  $seconds
     * @param  int|float  $minutes
     * @param  int|float  $hours
     * @param  int|float  $days
     * @param  int|float  $weeks
     * @param  int|float  $years
     * @return float|int
     */
    public static function milliseconds(
        int|float $seconds = 0,
        int|float $minutes = 0,
        int|float $hours = 0,
        int|float $days = 0,
        int|float $weeks = 0,
        int|float $years = 0
    ) {
        $seconds += static::seconds($minutes, $hours, $days, $weeks, $years);

        return $seconds * 1000;
    }

    /**
     * Convert the given time units to seconds.
     *
     * @param  int|float  $minutes
     * @param  int|float  $hours
     * @param  int|float  $days
     * @param  int|float  $weeks
     * @param  int|float  $years
     * @return float|int
     */
    public static function seconds(
        int|float $minutes = 0,
        int|float $hours = 0,
        int|float $days = 0,
        int|float $weeks = 0,
        int|float $years = 0
    ) {
        $minutes += static::minutes($hours, $days, $weeks, $years);

        return $minutes * 60;
    }

    /**
     * Convert the given time units to minutes.
     *
     * @param  int|float  $hours
     * @param  int|float  $days
     * @param  int|float  $weeks
     * @param  int|float  $years
     * @return float|int
     */
    public static function minutes(
        int|float $hours = 0,
        int|float $days = 0,
        int|float $weeks = 0,
        int|float $years = 0
    ) {
        $days += $years * 365;
        $days += $weeks * 7;
        $hours += $days * 24;

        return $hours * 60;
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
     * Ensure the "intl" PHP exntension is installed.
     *
     * @return void
     */
    protected static function ensureIntlExtensionIsInstalled()
    {
        if (! extension_loaded('intl')) {
            throw new RuntimeException('The "intl" PHP extension is required to use this method.');
        }
    }
}
