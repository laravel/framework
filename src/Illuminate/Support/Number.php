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
        $shortScale = [
            100 => 'Hundred',
            1000 => 'Thousand',
            1000000 => 'Million',
            1000000000 => 'Billion',
            1000000000000 => 'Trillion',
            1000000000000000 => 'Quadrillion',
            1000000000000000000 => 'Quintillion',
        ];

        $scale = 1;
        foreach ($shortScale as $value => $name) {
            if ($number < $value) {
                break;
            }
            $scale = $value;
        }

        return sprintf('%s %s', round($number / $scale, $precision), $shortScale[$scale]);
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
