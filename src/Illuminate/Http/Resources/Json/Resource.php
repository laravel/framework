<?php

namespace Illuminate\Http\Resources\Json;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Resources\Resource as BaseResource;

class Resource extends BaseResource implements ArrayAccess, JsonSerializable, Responsable, UrlRoutable
{
    /**
     * The attributes that should be hidden when serialized.
     *
     * @var array
     */
    public $hidden = [];

    /**
     * The attributes that should be visible when serialized.
     *
     * @var array
     */
    public $visible = [];

    /**
     * The callback that should customize the response.
     *
     * @var \Closure
     */
    public $callback;

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
        parent::__construct($resource);

        $this->callback = function () {
            //
        };
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toJson($request)
    {
        return $this->resourceToJson($request);
    }

    /**
     * Convert the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function resourceToJson($request)
    {
        $values = $this->resource->toArray();

        if (count($this->visible) > 0) {
            $values = array_intersect_key($values, array_flip($this->visible));
        }

        if (count($this->hidden) > 0) {
            $values = array_diff_key($values, array_flip($this->hidden));
        }

        return $values;
    }

    /**
     * Customize the response for a request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        //
    }

    /**
     * Define a custom callback that should customize the response.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function using($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Set the string that should wrap the outer-most JSON array.
     *
     * @param  string  $value
     * @return void
     */
    public static function wrap($value)
    {
        static::$wrap = $value;
    }

    /**
     * Disable wrapping of the outer-most JSON array.
     *
     * @param  string  $value
     * @return void
     */
    public static function withoutWrapping()
    {
        static::$wrap = null;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
        return $this->toJson(Container::getInstance()->make('request'));
    }
}
