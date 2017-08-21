<?php

namespace Illuminate\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\HeaderBag;

abstract class ResourceResponse implements Responsable
{
    /**
     * The underlying resource.
     *
     * @var mixed
     */
    public $resource;

    /**
     * The HTTP status code of the response.
     *
     * @var int
     */
    public $status;

    /**
     * The headers that should be present on the response.
     *
     * @var array
     */
    public $headers = [];

    /**
     * The callback that will customize the response.
     *
     * @var \Closure
     */
    public $callback;

    /**
     * Create a new resource repsonse.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;

        $this->withResponse(function ($request, $response) {
            return $response;
        });
    }

    /**
     * Set the HTTP status code on the response.
     *
     * @param  int  $status
     * @return $this
     */
    public function status($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set a header on the response.
     *
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    public function header($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Add an array of headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\HeaderBag|array  $headers
     * @return $this
     */
    public function withHeaders($headers)
    {
        if ($headers instanceof HeaderBag) {
            $headers = $headers->all();
        }

        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }

        return $this;
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
            call_user_func($this->callback, $request, $response);

            $this->resource->withResponse($request, $response);
        });
    }

    /**
     * Register a callback that will be used to customize the response.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function withResponse($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Calculate the appropriate status code for the response.
     *
     * @return int
     */
    protected function calculateStatus()
    {
        if ($this->status) {
            return $this->status;
        }

        return $this->resource instanceof Model &&
               $this->resource->wasRecentlyCreated ? 201 : 200;
    }
}
