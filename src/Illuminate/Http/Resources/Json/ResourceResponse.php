<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Support\Collection;
use Illuminate\Http\Resources\ResourceResponse as BaseResourceResponse;

class ResourceResponse extends BaseResourceResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return $this->build($request, response()->json(
            $this->wrap($this->resource->toJson($request)),
            $this->calculateStatus(), $this->resource->headers
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
     * Get the default data wrapper for the resource.
     *
     * @return string
     */
    protected function wrapper()
    {
        $class = get_class($this->resource);

        return $class::$wrap;
    }
}
