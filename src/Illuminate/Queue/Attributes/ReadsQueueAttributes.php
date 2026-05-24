<?php

namespace Illuminate\Queue\Attributes;

use Exception;
use Illuminate\Support\Attr;

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
            $instance = Attr::onClass($job)->recursive()->instance($attributeClass);

            if ($instance) {
                return $instance;
            }
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
