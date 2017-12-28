<?php

namespace Illuminate\Support;

use Illuminate\Support\Pluralizers\EnglishPluralizer;
use Illuminate\Support\Pluralizers\PluralizerInterface;
use UnexpectedValueException;

class Pluralizer
{
    /**
     * The pluralizer locale.
     *
     * @var string
     */
    protected static $locale = 'en';

    /**
     * The registered pluralizers.
     *
     * @var array
     */
    protected static $pluralizers  = [];

    /**
     * Register a localized pluralizer.
     *
     * @param string $locale
     * @param string $pluralizer
     *
     * @throws \UnexpectedValueException
     */
    public static function register($locale, $pluralizer)
    {
        $pluralizer = is_object($pluralizer) ? get_class($pluralizer) : $pluralizer;

        if (! in_array(PluralizerInterface::class, class_implements($pluralizer))) {
            throw new UnexpectedValueException('Pluralizer must implement PluralizerInterface.');
        }

        static::$pluralizers[$locale] = $pluralizer;
    }

    /**
     * Get the plural form of a word.
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        $pluralizer = static::pluralizer();

        $plural = $pluralizer::plural($value, $count);

        return static::matchCase($plural, $value);
    }

    /**
     * Get the singular form of a word.
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        $pluralizer = static::pluralizer();

        $singular = $pluralizer::singular($value);

        return static::matchCase($singular, $value);
    }

    /**
     * Attempt to match the case on two strings.
     *
     * @param  string  $value
     * @param  string  $comparison
     * @return string
     */
    protected static function matchCase($value, $comparison)
    {
        $functions = ['mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords'];

        foreach ($functions as $function) {
            if (call_user_func($function, $comparison) === $comparison) {
                return call_user_func($function, $value);
            }
        }

        return $value;
    }

    /**
     * Get the current pluralizer class if applicable.
     *
     * @return string
     */
    protected static function pluralizer()
    {
        if (array_key_exists(static::getLocale(), static::$pluralizers)) {
            return static::$pluralizers[static::$locale];
        }

        return EnglishPluralizer::class;
    }

    /**
     * Get the current pluralizer locale.
     *
     * @return string
     */
    public static function getLocale()
    {
        return static::$locale;
    }

    /**
     * Set the current pluralizer locale.
     *
     * @param  string  $locale
     * @return void
     */
    public static function setLocale($locale)
    {
        static::$locale = $locale;
    }
}
