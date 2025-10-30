<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection as JsonApiAnonymousResourceCollection;

class AnonymousResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    /**
     * Indicates if the collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = false;

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @param  string  $collects
     */
    public function __construct($resource, $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);
    }

    /**
     * Transform JSON resource to JSON:API.
     *
     * @param  array  $links
     * @param  array  $meta
     * @return Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection
     */
    public function asJsonApi(array $links = [], array $meta = [])
    {
        return new JsonApiAnonymousResourceCollection(
            $this->collection->map(fn ($resource) => $resource->asJsonApi($links, $meta)),
            $this->collects,
        );
    }
}
