<?php

namespace Illuminate\Support\Traits;

use Exception;
use ReflectionClass;

trait ReadsClassAttributes
{
    /**
     * Get a configuration value from an attribute, falling back to a property.
     *
     * @param  object  $target
     * @param  class-string  $attributeClass
     * @param  string|null  $property
     * @param  mixed  $default
     * @return mixed
     */
    protected function getAttributeValue($target, string $attributeClass, ?string $property = null, $default = null)
    {
        $reflection = new ReflectionClass($target);

        $defaultProperties = $reflection->getDefaultProperties();

        if (isset($target->{$property}) && $target->{$property} !== ($defaultProperties[$property] ?? null)) {
            return $target->{$property};
        }

        if ($instance = $this->getAttributeInstance($target, $attributeClass)) {
            return $this->extractAttributeValue($instance);
        }

        return $target->{$property} ?? $default;
    }

    /**
     * Extract the value from an attribute instance.
     *
     * @param  object  $instance
     * @return mixed
     */
    protected function extractAttributeValue($instance)
    {
        $properties = get_object_vars($instance);

        return $properties === [] ? true : reset($properties);
    }

    /**
     * Get an instance of the given attribute class from the target class or its parents.
     *
     * @param  object  $target
     * @param  class-string  $attributeClass
     * @return object|null
     */
    protected function getAttributeInstance($target, string $attributeClass)
    {
        $reflection = new ReflectionClass($target);

        try {
            do {
                $attributes = $reflection->getAttributes($attributeClass);

                if (count($attributes) > 0) {
                    return $attributes[0]->newInstance();
                }
            } while ($reflection = $reflection->getParentClass());
        } catch (Exception) {
            //
        }

        return null;
    }
}
