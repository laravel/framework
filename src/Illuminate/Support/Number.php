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
     * @param  ?string  $locale
     * @return string|false
     */
    public static function format(int|float $number, ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::DECIMAL);

        return $formatter->format($number);
    }

    /**
     * Convert the given number to its percentage equivalent.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  ?string  $locale
     * @return string|false
     */
    public static function toPercentage(int|float $number, int $precision = 0, ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::PERCENT);

        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);

        return $formatter->format($number / 100);
    }

    /**
     * Convert the given number to its currency equivalent.
     *
     * @param  int|float  $number
     * @param  string  $currency
     * @param  ?string  $locale
     * @return string|false
     */
    public static function toCurrency(int|float $number, string $currency = 'USD', ?string $locale = null)
    {
        static::ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($locale ?? static::$locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($number, $currency);
    }

    /**
     * Convert the given number to its file size equivalent.
     *
     * @param  int|float  $bytes
     * @param  int  $precision
     * @return string
     */
    public static function toFileSize(int|float $bytes, int $precision = 0)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', number_format($bytes, $precision), $units[$i]);
    }

    /**
     * Convert the number to its human readable equivalent.
     *
     * @param  int  $number
     * @param  int  $precision
     * @return string
     */
    public static function forHumans(int|float $number, int $precision = 0)
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
                return sprintf('-%s', static::forHumans(abs($number), $precision));
            case $number >= 1e15:
                return sprintf('%s quadrillion', static::forHumans($number / 1e15, $precision));
        }

        $numberExponent = floor(log10($number));
        $displayExponent = $numberExponent - ($numberExponent % 3);
        $number /= pow(10, $displayExponent);

        return trim(sprintf('%s %s', number_format($number, $precision), $units[$displayExponent]));
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
