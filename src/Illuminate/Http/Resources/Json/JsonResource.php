<?php

namespace Illuminate\Http\Resources\Json;

use ArrayAccess;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use Throwable;

class JsonResource implements ArrayAccess, JsonSerializable, Responsable, UrlRoutable
{
    use ConditionallyLoadsAttributes, DelegatesToResource;

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
     * The resource name resolver.
     *
     * @var callable
     */
    protected static $resourceNameResolver;

    /**
     * The resource namespace resolver.
     *
     * @var callable
     */
    protected static $resourceNamespaceResolver;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource = null)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new resource instance.
     *
     * @param  mixed  ...$parameters
     * @return static
     */
    public static function make(...$parameters)
    {
        if (($parameters[0] ?? null) instanceof Collection) {
            return static::collection($parameters[0]);
        }

        return new static(...$parameters);
    }

    /**
     * Create new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public static function collection($resource)
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
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

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        return is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forResource($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
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
     * Sets the resource for a model or collection of models
     *
     * @param mixed $resource
     * @return $this|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function for($resource)
    {
        if ($resource instanceof Collection) {
            return static::collection($resource);
        }

        $this->resource = $resource;

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

    /**
     * Get a new resource instance for the given model name.
     *
     * @param string $modelName
     * @param mixed ...$parameters
     * @return static
     */
    public static function resourceForModel(string $modelName, ...$parameters)
    {
        $resource = static::resolveResourceName($modelName);

        return $resource::make(...$parameters);
    }

    /**
     * Specify the callback that should be invoked to guess factory names based on dynamic relationship names.
     *
     * @param callable|null $callback
     * @return void
     */
    public static function guessResourceNamesUsing(callable $callback = null)
    {
        static::$resourceNameResolver = $callback;
    }

    /**
     * Specify the callback that should be invoked to get the namespace of the API resources.
     *
     * @param callable|null $callback
     * @return void
     */
    public static function resolveResourceNamespaceUsing(callable $callback = null)
    {
        static::$resourceNamespaceResolver = $callback;
    }

    /**
     * Get the namespace for the application's API resources.
     *
     * @return string
     */
    public static function resolveResourceNamespace()
    {
        $resolver = static::$resourceNamespaceResolver ?: function () {
            $appNamespace = static::appNamespace();

            return $appNamespace.'Http\\Resources\\';
        };

        return $resolver();
    }

    /**
     * Get the resource name for the given model name.
     *
     * @param  string  $modelName
     * @return string
     */
    public static function resolveResourceName(string $modelName)
    {
        $resolver = static::$resourceNameResolver ?: function (string $modelName) {
            $modelName = class_basename($modelName);
            $resourceNamespace = static::resolveResourceNamespace();
            $resourceName = Str::endsWith($resourceNamespace, '\\')
                ? $resourceNamespace.$modelName
                : $resourceNamespace.'\\'.$modelName;

            return class_exists($resourceName)
                ? $resourceName
                : $resourceName.'Resource';
        };

        return $resolver($modelName);
    }

    /**
     * Get the application namespace for the application.
     *
     * @return string
     */
    protected static function appNamespace()
    {
        try {
            return Container::getInstance()
                ->make(Application::class)
                ->getNamespace();
        } catch (Throwable $e) {
            return 'App\\';
        }
    }
}
