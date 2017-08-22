<?php

namespace Illuminate\Http;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Pagination\AbstractPaginator;

class ResourceCollection extends Resource
{
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
     * Map the given collection resource into its individual resources.
     *
     * @param  mixed  $resource
     * @return mixed
     */
    protected function collectResource($resource)
    {
        if (! $this->collects) {
            throw new Exception('The ['.get_class($this).'] resource must specify the models it collects.');
        }

        $this->collection = $resource->mapInto($this->collects);

        return $resource instanceof Collection
                    ? $this->collection
                    : $resource->setCollection($this->collection);
    }

    /**
     * Create a new JSON resource response for the given resource.
     *
     * @return \App\ResourceResponse
     */
    public function json()
    {
        return $this->resource instanceof AbstractPaginator
                    ? new Resources\PaginatedJsonResourceResponse($this)
                    : parent::json();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toJson($request)
    {
        $data = $this->resource->map(function ($item) use ($request) {
            return $item->toJson($request);
        })->all();

        return static::$wrap ? [static::$wrap => $data] : $data;
    }
}
