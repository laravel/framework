<?php

namespace Illuminate\Support;

use Doctrine\Common\Inflector\Inflector;

class Pluralizer
{
    /**
     * Uncountable word forms.
     *
     * @var array
     */
    public static $uncountable = [
        'audio',
        'beef',
        'bison',
        'bread',
        'butter',
        'chaos',
        'chassis',
        'compensation',
        'coreopsis',
        'courage',
        'data',
        'deer',
        'education',
        'emoji',
        'equipment',
        'evidence',
        'feedback',
        'firmware',
        'fish',
        'furniture',
        'gold',
        'hardware',
        'help',
        'information',
        'jedi',
        'jewelry',
        'knowledge',
        'love',
        'luck',
        'metadata',
        'milk',
        'money',
        'moose',
        'mud',
        'news',
        'nutrition',
        'offspring',
        'pasta',
        'plankton',
        'pokemon',
        'police',
        'pork',
        'rain',
        'rice',
        'series',
        'sheep',
        'silence',
        'software',
        'species',
        'sugar',
        'swine',
        'traffic',
        'water',
        'weather',
        'wheat',
        'wine',
    ];

    /**
     * Get the plural form of an English word.
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        if ((int) $count === 1 || static::uncountable($value)) {
            return $value;
        }

        $plural = Inflector::pluralize($value);

        return static::matchCase($plural, $value);
    }

    /**
     * Get the singular form of an English word.
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        $singular = Inflector::singularize($value);

        return static::matchCase($singular, $value);
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
            if (call_user_func($function, $comparison) === $comparison) {
                return call_user_func($function, $value);
            }
        }

        return $value;
    }
}
