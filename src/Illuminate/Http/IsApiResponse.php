<?php

namespace Illuminate\Http;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Pagination\AbstractPaginator;

trait IsApiResponse
{
    /**
     * The status code for the response.
     *
     * @var int
     */
    public $status = 200;

    /**
     * The meta data associated with the response.
     *
     * @var array
     */
    public $metadata = [];

    /**
     * The headers sent with the response.
     *
     * @var array
     */
    public $headers = [];

    /**
     * The options used while encoding data to JSON.
     *
     * @var int
     */
    public $encodingOptions = 0;

    /**
     * Get a displayable API output for the given object.
     *
     * @param  mixed  $object
     * @return array
     */
    public function transform($object = null)
    {
        $object = $object ?? $this->resource;

        $meta = $this->metadata;

        $data = $object instanceof Collection || $object instanceof AbstractPaginator
                    ? $object->map([$this, 'transformResource'])->toArray()
                    : $this->transformResource($object);

        if ($object instanceof AbstractPaginator) {
            $meta = array_merge($meta, array_except($object->toArray(), ['data']));
        }

        return array_filter(compact('data', 'meta'));
    }

    /**
     * Set the status code for the response.
     *
     * @param  int  $status
     * @return $this
     */
    public function withStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Add meta data to be associated with the response.
     *
     * @param  array  $data
     * @return $this
     */
    public function withMeta($data)
    {
        $this->metadata = $data;

        return $this;
    }

    /**
     * Add an array of headers to the response.
     *
     * @param  array  $headers
     * @return $this
     */
    public function withHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set the options used while encoding data to JSON.
     *
     * @param  int  $options
     * @return $this
     */
    public function withEncodingOptions($options)
    {
        $this->encodingOptions = $options;

        return $this;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \InvalidArgumentException
     */
    public function toResponse($request)
    {
        if (isset($this->resource)) {
            return response()->json($this->transform(),
                $this->status, $this->headers, $this->encodingOptions
            );
        }

        throw new InvalidArgumentException(static::class.' must implement toResponse() or have a $resource property.');
    }

    /**
     * Create a new instance of the response.
     *
     * @param  array  ...$args
     * @return ApiResponseTrait
     */
    public static function with(...$args)
    {
        return new self(...$args);
    }

    /**
     * Transform the given resource for the API output.
     *
     * @param  mixed  $resource
     * @return mixed
     */
    abstract public function transformResource($resource);
}
