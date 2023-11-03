<?php

namespace Illuminate\Support;

class Number
{
    protected static array $formatsByLocale = [
        'en' => [
            'precision' => 2,
            'format' => '%s%s',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
        ],
        'fr' => [
            'precision' => 2,
            'format' => '%s %s',
            'thousands_separator' => ' ',
            'decimal_separator' => ',',
        ],
        'de' => [
            'precision' => 2,
            'format' => '%s %s',
            'thousands_separator' => '.',
            'decimal_separator' => ',',
        ]
    ];

    /**
     * Percentage format with locale support.
     *
     * @param  int|float  $value
     * @param  array  $options
     * @return string
     */
    public static function percentage(int|float $value, array $options = []): string
    {
        $defaults = [
            'strip_insignificant_zeros' => false,
        ];
        $locale = $options['locale'] ?? 'en';
        $options = array_merge($defaults, self::getFormatByLocale($locale), $options);

        $formatted = self::format($value, $options);
        if ($options['strip_insignificant_zeros']) {
            $formatted = rtrim($formatted, '0\.');
        }

        return sprintf($options['format'], $formatted, '%');
    }

    /**
     * Format the given number.
     *
     * @param  int|float  $value
     * @param  array  $options
     * @return string
     */
    public static function format(int|float $value, array $options = []): string
    {
        $locale = $options['locale'] ?? 'en';

        $options = array_merge(self::getFormatByLocale($locale), $options);
        return number_format($value, $options['precision'], $options['decimal_separator'], $options['thousands_separator']);
    }

    /**
     * Return the format options for the given locale.
     *
     * @param  string  $locale
     * @return array
     */
    protected static function getFormatByLocale(string $locale = 'en'): array
    {
        if (isset(self::$formatsByLocale[$locale])) {
            return self::$formatsByLocale[$locale];
        }

        throw new \RuntimeException("Unsupported locale '$locale'");
    }
}
