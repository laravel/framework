<?php

namespace Illuminate\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Macroable;
use NumberFormatter;
use RuntimeException;

class Number
{
    use Macroable;

    /**
     * Format the number according to the current locale.
     *
     * @param  float|int  $number
     * @param  ?string  $locale
     * @return false|string
     */
    public static function format($number, $locale = null)
    {
        static::needsIntlExtension();

        $formatter = new NumberFormatter($locale ?? App::getLocale(), NumberFormatter::DECIMAL);

        return $formatter->format($number);
    }

    /**
     * Spell out the number according to the current locale.
     *
     * @param  float|int  $number
     * @param  ?string  $locale
     * @return false|string
     */
    public static function spellout($number, $locale = null)
    {
        static::needsIntlExtension();

        $formatter = new NumberFormatter($locale ?? App::getLocale(), NumberFormatter::SPELLOUT);

        return $formatter->format($number);
    }

    /**
     * Format the number to a percent format.
     *
     * @param  float|int  $number
     * @param  int  $precision
     * @param  string  $locale
     * @return false|string
     */
    public static function toPercent($number, $precision = 2, $locale = 'en')
    {
        static::needsIntlExtension();

        $formatter = new NumberFormatter($locale, NumberFormatter::PERCENT);

        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return $formatter->format($number / 100);
    }

    /**
     * Format the number to a currency format.
     *
     * @param  float|int  $number
     * @param  string  $currency
     * @param  ?string  $locale
     * @return false|string
     */
    public static function toCurrency($number, $currency = 'USD', $locale = null)
    {
        static::needsIntlExtension();

        $formatter = new NumberFormatter($locale ?? App::getLocale(), NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($number, $currency);
    }

    /**
     * Format the number of bytes to a human-readable string.
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    public static function bytesToHuman($bytes, $precision = 2)
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', round($bytes, $precision), $units[$i]);
    }

    /**
     * Format the number to a fluent human-readable string.
     *
     * @param  int  $number
     * @param  int  $precision
     * @return string
     */
    public static function toHuman($number, $precision = 2)
    {
        $units = [
            0 => '',
            1 => 'ten',
            2 => 'hundred',
            3 => 'thousand',
            6 => 'million',
            9 => 'billion',
            12 => 'trillion',
            15 => 'quadrillion',
            -1 => 'deci',
            -2 => 'centi',
            -3 => 'mili',
            -6 => 'micro',
            -9 => 'nano',
            -12 => 'pico',
            -15 => 'femto'
        ];

        $numberExponent = floor(log10($number));
        $displayExponent = $numberExponent - ($numberExponent % 3);
        $number /= pow(10, $displayExponent);

        $unit = $units[$displayExponent];

        if (! $unit && $displayExponent > 0) {
            $unit = $units[max(array_keys($units))];
            $number *= 1000;
        }

        return trim(sprintf('%s %s', round($number, $precision), $unit));
    }

    /**
     * Some of the helper methods are wrappers for the PHP intl extension,
     * and thus require it to be installed on the server. If it's not
     * installed, we instead throw an exception from this method.
     *
     * @return void
     */
    protected static function needsIntlExtension()
    {
        if (! extension_loaded('intl')) {
            throw new RuntimeException('The intl PHP extension is required to use this helper.');
        }
    }
}
