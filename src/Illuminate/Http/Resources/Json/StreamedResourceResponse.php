<?php

namespace Illuminate\Http\Resources\Json;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StreamedResourceResponse extends ResourceResponse
{

    /**
     * The underlying resource.
     *
     * @var JsonResource
     */
    public $resource;

    /**
     * Create a new resource response.
     *
     * @param  JsonResource  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create an Streamed HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        if (!$this->resource->stream) return parent::toResponse($request);
        return tap(response()->streamJson(
            $this->wrap(
                $this->resource->toStream($request),
                $this->resource->with($request),
                $this->resource->additional
            ),
            $this->calculateStatus(),
            [],
            $this->resource->jsonOptions()
        ), function ($response) use ($request) {
            $response->original = $this->resource->resource;

            $this->resource->withResponse($request, $response);
        });
    }

    /**
     * Wrap the given data if necessary.
     *
     * @param  \Illuminate\Support\Collection|array|Generator  $data
     * @param  array  $with
     * @param  array  $additional
     * @return array
     */
    protected function wrap($data, $with = [], $additional = [])
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        if ($data instanceof Generator) {
            $data = [($this->wrapper() ?? 'data') => $data];
        } elseif ($this->haveDefaultWrapperAndDataIsUnwrapped($data)) {
            $data = [$this->wrapper() => $data];
        } elseif ($this->haveAdditionalInformationAndDataIsUnwrapped($data, $with, $additional)) {
            $data = [($this->wrapper() ?? 'data') => $data];
        }

        return array_merge_recursive($data, $with, $additional);
    }
}
