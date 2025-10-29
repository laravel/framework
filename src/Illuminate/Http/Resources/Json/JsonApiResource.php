<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\ResolvesJsonApiSpecifications;

    /**
     * The resource's "version" for JSON:API.
     *
     * @var array{version?: string, ext?: array, profile?: array, meta?: array}
     */
    public static $jsonApiInformation = [];

    public function fields(Request $request)
    {
        return [
            //
        ];
    }

    public function relationships(Request $request)
    {
        return [
            //
        ];
    }

    public function meta(Request $request)
    {
        return [
            //
        ];
    }

    public function id(Request $request)
    {
        return $this->resolveResourceIdentifier($request);
    }

    public function type(Request $request)
    {
        return $this->resolveResourceType($request);
    }

    public function toLinks(Request $request)
    {
        return [
            //
        ];
    }

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
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array{id: string, type: string, attributes?: stdClass, relationships?: stdClass, meta?: stdClass, links?: stdClass}
     */
    #[\Override]
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id($request),
            'type' => $this->type($request),
            ...(new Collection([
                'attributes' => $this->resolveResourceAttributes($request),
                // 'relationships' => $this->resolveRelationshipsAsIdentifiers($request)->all(),
                // 'links' => self::parseLinks(array_merge($this->toLinks($request), $this->links)),
                // 'meta' => $this->resolveMetaInformations($request),
            ]))->filter()->map(fn ($value) => (object) $value),
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
        return (new JsonApiResourceResponse($this))->toResponse($request);
    }

    /**
     * Customize the outgoing response for the resource.
     */
    #[\Override]
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Content-type', 'application/vnd.api+json');
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
        return new JsonApiResourceCollection($resource, static::class);
    }

    /**
     * Flush the resource's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        parent::flushState();

        static::$jsonApiInformation = [];
    }
}
