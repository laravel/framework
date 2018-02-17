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
        'advice',
        'air',
        'alcohol',
        'art',
        'bison',
        'beauty',
        'beef',
        'blood',
        'bread',
        'butter',
        'cattle',
        'chassis',
        'cheese',
        'chocolate',
        'coffee',
        'compensation',
        'coreopsis', 
        'cotton',
        'data',
        'deer',
        'education',
        'electricity',
        'entertainment',
        'emoji',
        'equipment',
        'evidence',
        'feedback',
        'firmware',
        'fish',
        'flour',
        'food',
        'furniture',
        'gold',
        'grass',
        'ground',
        'hardware',
        'happiness',
        'history',
        'honey',
        'hope',
        'information',
        'ice',
        'jam',
        'jedi',
        'kin',
        'knowledge',
        'lamb',
        'lightning',
        'literature',
        'love',
        'luck',
        'luggage',
        'metadata',
        'milk',
        'mist',
        'money',
        'moose',
        'meat',
        'music',
        'news',
        'noise',
        'nutrition',
        'offspring',
        'oil',
        'oxygen',    
        'paper',
        'patience',
        'pay',
        'peace',
        'pepper',
        'petrol',
        'plankton',
        'plastic',
        'pokemon',
        'police',
        'pork',
        'power',
        'pressure',
        'progress',
        'rain',
        'research',
        'rice',
        'safety',
        'salad',
        'series',
        'salt',
        'sand',
        'sheep',
        'shopping',
        'silver',
        'snow',
        'software',
        'space',
        'species',
        'speed',
        'sport',
        'steam',
        'success',
        'sugar',
        'sunshine',
        'swine',
        'tea',
        'tennis',
        'time',
        'toothpaste',
        'traffic',
        'trouble',
        'vinegar',
        'water',
        'weather',
        'wheat',
        'wine',
        'wood',
        'wool',
        'work',
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
