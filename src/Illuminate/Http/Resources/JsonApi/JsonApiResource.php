<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class JsonApiResource extends JsonResource
{
    use Concerns\ResolvesJsonApiSpecifications;

    /**
     * The resource's "version" for JSON:API.
     *
     * @var array{version?: string, ext?: array, profile?: array, meta?: array}
     */
    public static $jsonApiInformation = [];

    /**
     * Set the JSON:API version for the request.
     *
     * @param  string  $version
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
     * Resource "links" for JSON:API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function links(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * Resource "meta" for JSON:API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function meta(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * Resource "id" for JSON:API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    public function id(Request $request)
    {
        return $this->resolveResourceIdentifier($request);
    }

    /**
     * Resource "type" for JSON:API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    public function type(Request $request)
    {
        return $this->resolveResourceType($request);
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array{included?: array<int, JsonApiResource>, jsonapi: ServerImplementation}
     */
    #[\Override]
    public function with($request)
    {
        return array_filter([
            'included' => $this->resolveResourceIncluded($request),
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
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[\Override]
    public function toResponse($request)
    {
        return (new ResourceResponse($this))->toResponse($request);
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\JsonResponse  $response
     * @return void
     */
    #[\Override]
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Content-type', 'application/vnd.api+json');
    }

    /**
     * Transform JSON resource to JSON:API.
     *
     * @return $this
     */
    #[\Override]
    public function asJsonApi()
    {
        return $this;
    }

    /**
     * Create a new resource collection instance.
     *
     * @param  mixed  $resource
     * @return \Illuminate\Http\Resources\JsonApi\JsonApiResourceCollection<int, mixed>
     */
    #[\Override]
    protected static function newCollection($resource)
    {
        return new ResourceCollection($resource, static::class);
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
