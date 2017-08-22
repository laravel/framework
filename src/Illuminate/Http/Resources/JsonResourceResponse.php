<?php

namespace Illuminate\Http\Resources;

use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonResourceResponse extends ResourceResponse
{
    /**
     * The extra data that should be added to the response.
     *
     * @var array
     */
    public $with = [];

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        if (! method_exists($this->resource, 'toJson')) {
            throw new HttpException(406);
        }

        return $this->build($request, response()->json(
            array_merge_recursive($this->wrap($this->resource->toJson($request)), $this->with),
            $this->calculateStatus(), $this->headers
        ));
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

        if ($this->haveDefaultWrapperAndDataIsUnwrapped($data)) {
            $data = [$this->wrapper() => $data];
        } elseif ($this->haveAdditionalInformationAndDataIsUnwrapped($data)) {
            $data = [($this->wrapper() ?? 'data') => $data];
        }

        return $data;
    }

    /**
     * Determine if we have a default wrapper and the given data is unwrapped.
     *
     * @param  array  $data
     * @return bool
     */
    protected function haveDefaultWrapperAndDataIsUnwrapped($data)
    {
        return $this->wrapper() && ! array_key_exists($this->wrapper(), $data);
    }

    /**
     * Determine if "with" data has been added and our data is unwrapped.
     *
     * @param  array  $data
     * @return bool
     */
    protected function haveAdditionalInformationAndDataIsUnwrapped($data)
    {
        return ! empty($this->with) &&
              (! $this->wrapper() ||
               ! array_key_exists($this->wrapper(), $data));
    }

    /**
     * Add the given array to the response body.
     *
     * @param  array  $values
     * @return $this
     */
    public function with(array $values)
    {
        $this->with = array_merge($this->with, $values);

        return $this;
    }

    /**
     * Get the default data wrapper for the resource.
     *
     * @return string
     */
    protected function wrapper()
    {
        $class = get_class($this->resource);

        return $class::$wrap;
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
        return tap(parent::build($request, $response), function ($response) use ($request) {
            $this->resource->withJsonResponse($request, $response);
        });
    }
}
