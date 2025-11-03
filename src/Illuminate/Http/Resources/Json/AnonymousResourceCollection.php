<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Support\Arr;

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
     * Resolve all resources and return as a Collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toCollection()
    {
        return $this->collection->map(function ($resource) {
            return $resource->resolve(request());
        });
    }

    /**
     * Apply only() to each item in the collection.
     *
     * @param  array|string  $keys
     * @return array
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return $this->toCollection()->map(function ($item) use ($keys) {
            return Arr::only($item, $keys);
        })->values()->all();
    }

    /**
     * Apply except() to each item in the collection.
     *
     * @param  array|string  $keys
     * @return array
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return $this->toCollection()->map(function ($item) use ($keys) {
            return Arr::except($item, $keys);
        })->values()->all();
    }

    /**
     * Filter the collection.
     *
     * @param  callable|null  $callback
     * @return array
     */
    public function filter($callback = null)
    {
        return $this->toCollection()->filter($callback)->values()->all();
    }

    /**
     * Map over the collection.
     *
     * @param  callable  $callback
     * @return array
     */
    public function map($callback)
    {
        return $this->toCollection()->map($callback)->values()->all();
    }

    /**
     * Pluck values from the collection.
     *
     * @param  string|int  $value
     * @param  string|int|null  $key
     * @return array
     */
    public function pluck($value, $key = null)
    {
        return $this->toCollection()->pluck($value, $key)->all();
    }
}
