<?php

namespace Illuminate\Http\Resources\Json;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Resources\DelegatesToResource;

class Resource implements ArrayAccess, JsonSerializable, Responsable, UrlRoutable
{
    use DelegatesToResource;

    /**
     * The resource instance.
     *
     * @var mixed
     */
    public $resource;

    /**
     * The additional data that should be added to the top-level resource array.
     *
     * @var array
     */
    public $with = [];

    /**
     * The additional meta data that should be added to the resource response.
     *
     * Added during response construction by the developer.
     *
     * @var array
     */
    public $additional = [];

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'data';

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new resource instance.
     *
     * @param  dynamic  $parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        return new static(...$parameters);
    }

    /**
     * Create new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return mixed
     */
    public static function collection($resource)
    {
        return new class($resource, get_called_class()) extends ResourceCollection {
            /**
             * @var string
             */
            public $collects;

            /**
             * Create a new anonymous resource collection.
             *
             * @param  mixed  $resource
             * @param  string  $collects
             * @return void
             */
            public function __construct($resource, $collects)
            {
                $this->collects = $collects;

                parent::__construct($resource);
            }
        };
    }

    /**
     * Resolve the resource to an array.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return array
     */
    public function resolve($request = null)
    {
        $data = $this->toArray(
            $request = $request ?: Container::getInstance()->make('request')
        );

        if (is_array($data)) {
            $data = $data;
        } elseif ($data instanceof Arrayable || $data instanceof Collection) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    /**
     * Filter the given data, removing any optional values.
     *
     * @param  array  $data
     * @return array
     */
    protected function filter($data)
    {
        $index = -1;

        foreach ($data as $key => $value) {
            $index++;

            if (is_array($value)) {
                $data[$key] = $this->filter($value);

                continue;
            }

            if (is_numeric($key) && $value instanceof MergeValue) {
                return $this->merge($data, $index, $this->filter($value->data));
            }

            if ($value instanceof MissingValue ||
                ($value instanceof self &&
                $value->resource instanceof MissingValue)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Merge the given data in at the given index.
     *
     * @param  array  $data
     * @param  int  $index
     * @param  array  $merge
     * @return array
     */
    protected function merge($data, $index, $merge)
    {
        if (array_values($data) === $data) {
            return array_merge(
                array_merge(array_slice($data, 0, $index, true), $merge),
                $this->filter(array_slice($data, $index + 1, null, true))
            );
        }

        return array_slice($data, 0, $index, true) +
                $merge +
                $this->filter(array_slice($data, $index + 1, null, true));
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return $this->with;
    }

    /**
     * Add additional meta data to the resource response.
     *
     * @param  array  $data
     * @return $this
     */
    public function additional(array $data)
    {
        $this->additional = $data;

        return $this;
    }

    /**
     * Customize the response for a request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\JsonResponse  $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        //
    }

    /**
     * Retrieve a value based on a given condition.
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function when($condition, $value, $default = null)
    {
        if ($condition) {
            return value($value);
        }

        return func_num_args() === 3 ? value($default) : new MissingValue;
    }

    /**
     * Merge a value based on a given condition.
     *
     * @param  bool  $condition
     * @param  mixed  $value
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function mergeWhen($condition, $value)
    {
        return $condition ? new MergeValue(value($value)) : new MissingValue;
    }

    /**
     * Merge the given attributes.
     *
     * @param  array  $attributes
     * @return \Illuminate\Http\Resources\MergeValue
     */
    protected function attributes($attributes)
    {
        return new MergeValue(
            Arr::only($this->resource->toArray(), $attributes)
        );
    }

    /**
     * Retrieve a relationship if it has been loaded.
     *
     * @param  string  $relationship
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenLoaded($relationship)
    {
        if ($this->resource->relationLoaded($relationship)) {
            return $this->resource->{$relationship};
        }

        return new MissingValue;
    }

    /**
     * Execute a callback if the given pivot table has been loaded.
     *
     * @param  string  $table
     * @param  mixed  $value
     * @param  mixed  $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenPivotLoaded($table, $value, $default = null)
    {
        if (func_num_args() === 2) {
            $default = new MissingValue;
        }

        return $this->when(
            $this->pivot && ($this->pivot instanceof $table || $this->pivot->getTable() === $table),
            ...[$value, $default]
        );
    }

    /**
     * Transform the given value if it is present.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected function transform($value, callable $callback, $default = null)
    {
        return transform(
            $value, $callback, func_num_args() === 3 ? $default : new MissingValue
        );
    }

    /**
     * Set the string that should wrap the outer-most resource array.
     *
     * @param  string  $value
     * @return void
     */
    public static function wrap($value)
    {
        static::$wrap = $value;
    }

    /**
     * Disable wrapping of the outer-most resource array.
     *
     * @return void
     */
    public static function withoutWrapping()
    {
        static::$wrap = null;
    }

    /**
     * Transform the resource into an HTTP response.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($request = null)
    {
        return $this->toResponse(
            $request ?: Container::getInstance()->make('request')
        );
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return (new ResourceResponse($this))->toResponse($request);
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->resolve(Container::getInstance()->make('request'));
    }
}
