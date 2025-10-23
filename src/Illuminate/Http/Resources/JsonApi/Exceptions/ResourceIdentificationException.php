<?php

namespace Illuminate\Http\Resources\JsonApi\Exceptions;

class ResourceIdentificationException extends RuntimeException
{
     /**
     * Create a new unable to determine Resource ID exception for the given resource.
     *
     * @param  mixed  $resource
     * @return static
     */
    public static function attemptingToDetermineIdFor($resource)
    {
        $resourceType = is_object($resource) ? $resource::class : gettype($resource);

        return new self(sprintf(
            'Unable to resolve resource object id for [%s].', $resourceType
        ));
    }

    /**
     * Create a new unable to determine Resource type exception for the given resource.
     *
     * @param  mixed  $resource
     * @return self
     */
    public static function attemptingToDetermineTypeFor($resource)
    {
        $resourceType = is_object($resource) ? $resource::class : gettype($resource);

        return new self(sprintf(
            'Unable to resolve resource object type for [%s].', $resourceType
        ));
    }
}
