<?php

namespace Illuminate\Http;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractPaginator;

trait ApiResponseTrait
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
    public $metaData = [];

    /**
     * Get a displayable API output for the given object.
     *
     * @param  mixed  $object
     * @return array
     */
    public function transform($object = null)
    {
        $object = $object ?? $this->resource;

        $meta = $this->metaData;

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
        $this->metaData = $data;

        return $this;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @return \Illuminate\Http\Response
     * @throws InvalidArgumentException
     */
    public function toResponse()
    {
        if (isset($this->resource)) {
            return response()->json($this->transform(), $this->status);
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
