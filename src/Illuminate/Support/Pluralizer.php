<?php

namespace Illuminate\Support;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

class Pluralizer
{
    /**
     * Uncountable word forms.
     *
     * @var string[]
     */
    public static $uncountable = [
          "accommodation",
          "advice",
          "aggression",
          "air",
          "aircraft",
          "applause",
          "art",
          "assistance",
          "attention",
          "audio",
          "baggage",
          "beauty",
          "beef",
          "billiards",
          "bison",
          "bravery",
          "bread",
          "business",
          "butter",
          "cattle",
          "chassis",
          "cheese",
          "chess",
          "compensation",
          "coreopsis",
          "courage",
          "curiosity",
          "currency",
          "data",
          "deer",
          "dirt",
          "dust",
          "education",
          "emoji",
          "entertainment",
          "equipment",
          "evidence",
          "faith",
          "feedback",
          "firmware",
          "fish",
          "flour",
          "freedom",
          "fruit",
          "fun",
          "furniture",
          "garbage",
          "gas",
          "gold",
          "grass",
          "grief",
          "guilt",
          "hair",
          "hardware",
          "health",
          "heat",
          "height",
          "help",
          "homework",
          "honey",
          "housework",
          "humor",
          "hunger",
          "information",
          "jam",
          "jedi",
          "judo",
          "kin",
          "knowledge",
          "land",
          "laughter",
          "lightning",
          "literature",
          "litter",
          "love",
          "luggage",
          "mail",
          "mathematics",
          "measles",
          "metadata",
          "milk",
          "money",
          "moose",
          "mud",
          "nature",
          "news",
          "nutrition",
          "obesity",
          "offspring",
          "oil",
          "pajamas",
          "paper",
          "pasta",
          "petrol",
          "physics",
          "plankton",
          "poetry",
          "pokemon",
          "police",
          "pork",
          "rain",
          "recommended",
          "related",
          "rice",
          "rubbish",
          "salmon",
          "salt",
          "satisfaction",
          "series",
          "sheep",
          "software",
          "species",
          "strength",
          "sugar",
          "sunshine",
          "swine",
          "tea",
          "thirst",
          "time",
          "traffic",
          "transportation",
          "travel",
          "underwear",
          "vision",
          "water",
          "weight",
          "wheat",
          "wine",
          "wisdom",
          "yoga"
        ];

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

        if ((int) abs($count) === 1 || static::uncountable($value) || preg_match('/^(.*)[A-Za-z0-9\x{0080}-\x{FFFF}]$/u', $value) == 0) {
            return $value;
        }

        $plural = static::inflector()->pluralize($value);

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
        $singular = static::inflector()->singularize($value);

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
        static $inflector;

        if (is_null($inflector)) {
            $inflector = InflectorFactory::createForLanguage('english')->build();
        }

        return $inflector;
    }
}
