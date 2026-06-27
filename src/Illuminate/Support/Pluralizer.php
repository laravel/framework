<?php

namespace Illuminate\Support;

use Doctrine\Inflector\InflectorFactory;

class Pluralizer
{
    /**
     * The cached inflector instance.
     *
     * @var static
     */
    protected static $inflector;

    /**
     * The language that should be used by the inflector.
     *
     * @var string
     */
    protected static $language = 'english';

    /**
     * Uncountable non-nouns word forms.
     *
     * Contains words supported by Doctrine/Inflector/Rules/English/Uninflected.php
     *
     * @var string[]
     */
    public static $uncountable = [
        'recommended',
        'related',
    ];

    /**
     * The cache of plural forms, keyed by the input value.
     *
     * @var array<string, string>
     */
    protected static $pluralCache = [];

    /**
     * The cache of singular forms, keyed by the input value.
     *
     * @var array<string, string>
     */
    protected static $singularCache = [];

    /**
     * Get the plural form of an English word.
     *
     * @param  string  $value
     * @param  int|array|\Countable  $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        if (is_countable($count)) {
            $count = count($count);
        }

        if ((int) abs($count) === 1) {
            return $value;
        }

        if (isset(static::$pluralCache[$value])) {
            return static::$pluralCache[$value];
        }

        if (static::uncountable($value) || preg_match('/^(.*)[A-Za-z0-9\x{0080}-\x{FFFF}]$/u', $value) == 0) {
            return static::$pluralCache[$value] = $value;
        }

        $plural = static::inflector()->pluralize($value);

        return static::$pluralCache[$value] = static::matchCase($plural, $value);
    }

    /**
     * Get the singular form of an English word.
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        if (isset(static::$singularCache[$value])) {
            return static::$singularCache[$value];
        }

        $singular = static::inflector()->singularize($value);

        return static::$singularCache[$value] = static::matchCase($singular, $value);
    }

    /**
     * Determine if the given value is uncountable.
     *
     * @param  string  $value
     * @return bool
     */
    protected static function uncountable($value)
    {
        return in_array(strtolower($value), static::$uncountable);
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
            if ($function($comparison) === $comparison) {
                return $function($value);
            }
        }

        return $value;
    }

    /**
     * Get the inflector instance.
     *
     * @return \Doctrine\Inflector\Inflector
     */
    public static function inflector()
    {
        if (is_null(static::$inflector)) {
            static::$inflector = InflectorFactory::createForLanguage(static::$language)->build();
        }

        return static::$inflector;
    }

    /**
     * Specify the language that should be used by the inflector.
     *
     * @param  string  $language
     * @return void
     */
    public static function useLanguage(string $language)
    {
        static::$language = $language;

        static::$inflector = null;
        static::$pluralCache = [];
        static::$singularCache = [];
    }
}
