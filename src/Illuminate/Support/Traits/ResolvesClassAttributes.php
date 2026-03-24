<?php

declare(strict_types=1);

namespace Illuminate\Support\Traits;

use Exception;
use ReflectionClass;

trait ResolvesClassAttributes
{
    /**
     * Cache of resolved class attributes.
     *
     * @var array<class-string<self>, array<class-string, mixed>>
     */
    protected static array $classAttributes = [];

    /**
     * Resolve a class attribute value from the resource.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attributeClass
     * @param  string|null  $property
     * @param  string|null  $class
     * @return mixed
     */
    protected static function resolveClassAttribute(string $attributeClass, ?string $property = null, ?string $class = null)
    {
        $class = $class ?? static::class;

        $cacheKey = $class.'@'.$attributeClass;

        if (array_key_exists($cacheKey, static::$classAttributes)) {
            return static::$classAttributes[$cacheKey];
        }

        try {
            $reflection = new ReflectionClass($class);

            do {
                $attributes = $reflection->getAttributes($attributeClass);

                if (count($attributes) > 0) {
                    $instance = $attributes[0]->newInstance();

                    return static::$classAttributes[$cacheKey] = $property ? $instance->{$property} : $instance;
                }
            } while ($reflection = $reflection->getParentClass());
        } catch (Exception) {
            //
        }

        return static::$classAttributes[$cacheKey] = null;
    }
}
