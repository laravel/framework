<?php

namespace Illuminate\Http\Resources\Json;

use IteratorAggregate;
use Illuminate\Support\Collection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Http\Resources\CollectsResources;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ResourceCollection extends Resource implements IteratorAggregate
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var \Illuminate\Support\Collection
     */
    public $collection;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Eager load relations on the resource.
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function load($relations)
    {
        $this->collection = (new EloquentCollection($this->collection->all()))
                    ->load($relations)->toBase();

        return $this;
    }

    /**
     * Eager load relations on the resource if they are not already eager loaded.
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function loadMissing($relations)
    {
        return $this->load($relations);
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map->toArray($request)->all();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return $this->resource instanceof AbstractPaginator
                    ? (new PaginatedResourceResponse($this))->toResponse($request)
                    : parent::toResponse($request);
    }
}
