<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait SelfMorphModel
{
    protected static $morphTypes = [];

    /**
     * register a morph type to a class.
     */
    public static function registerMorph($alias)
    {
        self::$morphTypes[$alias] = static::class;
    }

    /**
     * find the class morph type(from class name to alias).
     */
    public static function getMorphType($className = null)
    {
        $className = $className ? $className : static::class;
        
        return array_search($className, self::$morphTypes);
    }

    /**
     * get all morph types(alias).
     */
    public static function getMorphTypes()
    {
        return array_keys(self::$morphTypes);
    }

    protected function newMorphInstance(array $attributes, $fillAttributes = true)
    {
        $morphType = $attributes['morph_type'] ?? false;
        if ($morphType && isset(self::$morphTypes[$morphType])) {
            $class = self::$morphTypes[$morphType];

            return new $class($fillAttributes ? $attributes : []);
        }

        return new static($fillAttributes ? $attributes : []);
    }
}
