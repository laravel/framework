<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Responsable;

class ResourceResponse implements Responsable
{
    /**
     * The underlying resource.
     *
     * @var mixed
     */
    public $resource;

    /**
     * Create a new resource repsonse.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return tap(response()->json(
            $this->wrap($this->resource->toJson($request)), $this->calculateStatus()
        ), function ($response) use ($request) {
            $this->resource->withResponse($request, $response);
        });
    }

    /**
     * Wrap the given data if necessary.
     *
     * @param  array  $data
     * @return array
     */
    protected function wrap($data)
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        return $this->wrapper() && ! array_key_exists($this->wrapper(), $data)
                     ? [$this->wrapper() => $data]
                     : $data;
    }

    /**
     * Get the default data wrapper for the resource.
     *
     * @return string
     */
    protected function wrapper()
    {
        return get_class($this->resource)::$wrap;
    }

    /**
     * Calculate the appropriate status code for the response.
     *
     * @return int
     */
    protected function calculateStatus()
    {
        return $this->resource->resource instanceof Model &&
               $this->resource->resource->wasRecentlyCreated ? 201 : 200;
    }
}
