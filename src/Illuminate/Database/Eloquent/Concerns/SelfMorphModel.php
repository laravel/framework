<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait SelfMorphModel
{
    protected static $morphTypes = [];

    /**
     * Register a morph type to a class.
     *
     * @param  string  $alias
     * @return void
     */
    public static function registerMorph($alias)
    {
        self::$morphTypes[$alias] = static::class;
    }

    /**
     * Find the class morph type(from class name to alias).
     *
     * @param  string|null  $className
     * @return string|null
     */
    public static function getMorphType($className = null)
    {
        $className = $className ? $className : static::class;

        return array_search($className, self::$morphTypes);
    }

    /**
     * Get all morph types(alias).
     *
     * @return array
     */
    public static function getMorphTypes()
    {
        return array_keys(self::$morphTypes);
    }

    /**
     * Create a new instance base on morph_type in $attributes.
     *
     * @param  array  $attributes
     * @param  bool  $fillWithAttributes if true will fill with $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
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
