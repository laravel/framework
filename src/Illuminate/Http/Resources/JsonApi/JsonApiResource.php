<?php

namespace Illuminate\Http\Resources\JsonApi;

use BadMethodCallException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class JsonApiResource extends JsonResource
{
    use Concerns\ResolvesJsonApiElements;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * The resource's "version" for JSON:API.
     *
     * @var array{version?: string, ext?: array, profile?: array, meta?: array}
     */
    public static $jsonApiInformation = [];

    /**
     * The resource's "links" for JSON:API.
     */
    protected array $jsonApiLinks = [];

    /**
     * The resource's "meta" for JSON:API.
     */
    protected array $jsonApiMeta = [];

    /**
     * Set the JSON:API version for the request.
     *
     * @return void
     */
    public static function configure(?string $version = null, array $ext = [], array $profile = [], array $meta = [])
    {
        static::$jsonApiInformation = array_filter([
            'version' => $version,
            'ext' => $ext,
            'profile' => $profile,
            'meta' => $meta,
        ]);
    }

    /**
     * Get the resource's ID.
     *
     * @return string
     */
    public function id(Request $request)
    {
        return $this->resolveResourceIdentifier($request);
    }

    /**
     * Get the resource's type.
     *
     * @return string
     */
    public function type(Request $request)
    {
        return $this->resolveResourceType($request);
    }

    /**
     * Get the resource's links.
     *
     * @return array
     */
    public function links(Request $request)
    {
        return $this->jsonApiLinks;
    }

    /**
     * Get the resource's meta information.
     *
     * @return array
     */
    public function meta(Request $request)
    {
        return $this->jsonApiMeta;
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function with($request)
    {
        return array_filter([
            'included' => $this->resolveIncludedResources($request),
            ...($implementation = static::$jsonApiInformation)
                ? ['jsonapi' => $implementation]
                : [],
        ]);
    }

    /**
     * Resolve the resource to an array.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return array
     */
    #[\Override]
    public function resolve($request = null)
    {
        return [
            'data' => $this->resolveResourceData($request),
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    #[\Override]
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Create a new resource collection instance.
     *
     * @param  mixed  $resource
     * @return \Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection
     */
    #[\Override]
    protected static function newCollection($resource)
    {
        return new AnonymousResourceCollection($resource, static::class);
    }

    /**
     * Set the string that should wrap the outer-most resource array.
     *
     * @param  string  $value
     * @return never
     *
     * @throws \RuntimeException
     */
    #[\Override]
    public static function wrap($value)
    {
        throw new BadMethodCallException(sprintf('Using %s() method is not allowed.', __METHOD__));
    }

    /**
     * Disable wrapping of the outer-most resource array.
     *
     * @return never
     */
    #[\Override]
    public static function withoutWrapping()
    {
        throw new BadMethodCallException(sprintf('Using %s() method is not allowed.', __METHOD__));
    }

    /**
     * Flush the resource's global state.
     *
     * @return void
     */
    #[\Override]
    public static function flushState()
    {
        parent::flushState();

        static::$jsonApiInformation = [];
    }
}
