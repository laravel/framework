<?php

namespace Illuminate\Http\Resources\JsonApi\Exceptions;

use Exception;

class UnknownRelationshipException extends Exception
{
    /**
     * Create a new unknown relationship exception for the given resource.
     *
     * @param  mixed  $resource
     * @return static
     */
    public static function from($resource)
    {
        $resourceType = is_object($resource) ? $resource::class : gettype($resource);

        return new self(sprintf(
            'Unknown relationship encountered. Relationships should always return a class that extends %s or %s. Instead found [%s].',
            JsonApiResource::class,
            JsonApiResourceCollection::class,
            $resourceType
        ));
    }
}
