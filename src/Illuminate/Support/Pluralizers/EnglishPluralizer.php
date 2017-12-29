<?php

namespace Illuminate\Support\Pluralizers;

use Doctrine\Common\Inflector\Inflector;

class EnglishPluralizer implements PluralizerInterface
{
    /**
     * Uncountable word forms.
     *
     * @var array
     * @var string
     */
    public static $uncountable = [
        'audio',
        'bison',
        'cattle',
        'chassis',
        'compensation',
        'coreopsis',
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
        'information',
        'jedi',
        'kin',
        'knowledge',
        'love',
        'metadata',
        'money',
        'moose',
        'news',
        'nutrition',
        'offspring',
        'plankton',
        'pokemon',
        'police',
        'rain',
        'rice',
        'series',
        'sheep',
        'software',
        'species',
        'swine',
        'traffic',
        'wheat',
    ];

    /**
     * Get the plural form of a word.
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

        return Inflector::pluralize($value);
    }

    /**
     * Get the singular form of a word.
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        return Inflector::singularize($value);
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
}
