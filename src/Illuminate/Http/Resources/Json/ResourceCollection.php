<?php

namespace Illuminate\Http\Resources\Json;

use Countable;
use IteratorAggregate;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Http\Resources\CollectsResources;

class ResourceCollection extends JsonResource implements Countable, IteratorAggregate
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
     * The extra data that can be used to construct each of the resource arrays.
     *
     * @var array
     */
    protected $extra = [];

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
     * Add extra data to construct each of the resource arrays.
     *
     * @param array $data
     * @return $this
     */
    public function using(array $data)
    {
        $this->extra = $data;

        return $this;
    }

    /**
     * Return the count of items in the resource collection.
     *
     * @return int
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->collection->map->using($this->extra);

        return $this->collection->map->toArray($request)->all();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return $this->resource instanceof AbstractPaginator
                    ? (new PaginatedResourceResponse($this))->toResponse($request)
                    : parent::toResponse($request);
    }
}
