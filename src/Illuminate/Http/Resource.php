<?php

namespace Illuminate\Http;

use Exception;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Responsable;

class Resource implements ArrayAccess, IteratorAggregate, JsonSerializable, Responsable, UrlRoutable
{
    use Macroable;

    /**
     * The resource instance.
     *
     * @var mixed
     */
    public $resource;

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
     * Create a resource response based on the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function response($request)
    {
        if (static::hasMacro($format = $request->format())) {
            return call_user_func_array(static::$macros[$format], $request);
        }

        if ($request->expectsJson()) {
            return $this->json();
        }

        switch ($format) {
            case 'html':
                return $this->html();

            case 'css':
                return $this->css();

            default:
                return $this->json();
        }
    }

    /**
     * Create a new HTML resource response for the given resource.
     *
     * @return \App\ResourceResponse
     */
    public function html()
    {
        return new Resources\HtmlResourceResponse(get_class($this), $this->resource);
    }

    /**
     * Create a new CSS resource response for the given resource.
     *
     * @return \App\ResourceResponse
     */
    public function css()
    {
        return new Resources\CssResourceResponse(get_class($this), $this->resource);
    }

    /**
     * Create a new JSON resource response for the given resource.
     *
     * @return \App\ResourceResponse
     */
    public function json()
    {
        return $this->resource instanceof AbstractPaginator
                    ? new Resources\PaginatedJsonResourceResponse(get_class($this), $this->resource)
                    : new Resources\JsonResourceResponse(get_class($this), $this->resource);
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toJson($request)
    {
        return $this->isCollectionResource() && $this->singularResource()
                    ? $this->collectionToJson($request)
                    : $this->resourceToJson($request);
    }

    /**
     * Determine if this resource is a collection resource.
     *
     * @return bool
     */
    protected function isCollectionResource()
    {
        return $this->resource instanceof Collection &&
                        Str::endsWith(get_class($this), 'Collection');
    }

    /**
     * Convert the collection into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function collectionToJson($request)
    {
        $data = $this->mapInto(
            $this->singularResource()
        )->map->toJson($request)->all();

        return static::$wrap ? [static::$wrap => $data] : $data;
    }

    /**
     * Get the singular version of this resource class, if applicable.
     *
     * @return string|null
     */
    protected function singularResource()
    {
        if (class_exists($class = Str::replaceLast('Collection', '', get_class($this)))) {
            return $class;
        }
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
     * Customize the response for a HTML request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withHtmlResponse($request, $response)
    {
        //
    }

    /**
     * Customize the response for a CSS request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withCssResponse($request, $response)
    {
        //
    }

    /**
     * Customize the response for a JSON request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withJsonResponse($request, $response)
    {
        //
    }

    /**
     * Get the value of the resource's route key.
     *
     * @return mixed
     */
    public function getRouteKey()
    {
        return $this->resource->getRouteKey();
    }

    /**
     * Get the route key for the resource.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->resource->getRouteKeyName();
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @return static
     */
    public function resolveRouteBinding($value)
    {
        throw new Exception("Resources may not be implicitly resolved from route bindings.");
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
        return $this->response($request)->toResponse($request);
    }

    /**
     * Get an iterator for the resource.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        if (is_array($this->resource)) {
            return new ArrayIterator($this->resource);
        } elseif ($this->resource instanceof IteratorAggregate) {
            return $this->resource->getIterator();
        }

        throw new Exception(
            "Unable to generate an iterator for this resource."
        );
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

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($this->resource[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->resource[$offset];
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->resource[$offset] = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->resource[$offset]);
    }

    /**
     * Determine if an attribute exists on the resource.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->resource->{$key});
    }

    /**
     * Unset an attribute on the resource.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->resource->{$key});
    }

    /**
     * Dynamically get properties from the underlying resource.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->resource->{$key};
    }

    /**
     * Dynamically pass method calls to the underlying resource.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->resource->{$method}(...$parameters);
    }
}
