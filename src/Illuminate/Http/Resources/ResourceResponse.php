<?php

namespace Illuminate\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Responsable;

abstract class ResourceResponse implements Responsable
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
     * Build the finished HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function build($request, $response)
    {
        return tap($response, function ($response) use ($request) {
            call_user_func($this->resource->callback, $request, $response);

            $this->resource->withResponse($request, $response);
        });
    }

    /**
     * Calculate the appropriate status code for the response.
     *
     * @return int
     */
    protected function calculateStatus()
    {
        if ($this->resource->status) {
            return $this->resource->status;
        }

        return $this->resource->resource instanceof Model &&
               $this->resource->resource->wasRecentlyCreated ? 201 : 200;
    }
}
