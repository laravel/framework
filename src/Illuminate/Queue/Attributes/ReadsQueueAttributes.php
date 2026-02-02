<?php

namespace Illuminate\Queue\Attributes;

use Exception;
use ReflectionClass;

trait ReadsQueueAttributes
{
    /**
     * Get a configuration value from an attribute, falling back to a property.
     *
     * @param  object  $job
     * @param  string  $attributeClass
     * @param  string|null  $property
     * @param  mixed  $default
     * @return mixed
     */
    protected function getAttributeValue($job, string $attributeClass, ?string $property = null, $default = null)
    {
        try {
            $reflection = new ReflectionClass($job);

            do {
                $attributes = $reflection->getAttributes($attributeClass);

                if (count($attributes) > 0) {
                    return $this->extractAttributeValue($attributes[0]->newInstance());
                }
            } while ($reflection = $reflection->getParentClass());
        } catch (Exception) {
            //
        }

        if ($property !== null) {
            return $job->{$property} ?? $default;
        }

        return $default;
    }

    /**
     * Extract the value from an attribute instance.
     *
     * @param  object  $instance
     * @return mixed
     */
    protected function extractAttributeValue($instance)
    {
        if ($instance instanceof DeleteWhenMissingModels || $instance instanceof FailOnTimeout) {
            return true;
        }

        // For value attributes, return the first property value...
        $properties = get_object_vars($instance);

        return reset($properties);
    }
}
