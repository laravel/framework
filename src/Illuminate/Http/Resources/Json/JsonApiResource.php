<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

abstract class JsonApiResource extends JsonResource
{
    use Concerns\InteractsWithMetaInformations;

    /**
     * The resource's "version" for JSON:API.
     *
     * @var array{version?: string, ext?: array, profile?: array, meta?: array}
     */
    public static $jsonApiInformation = [];

    public function attributes(Request $request): array
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
        if ($this->resource instanceof Model) {
            return $this->resource->getKey();
        }

        throw new RuntimeException('Unable to determine "id"');
    }

    public function type(Request $request)
    {
        if ($this->resource instanceof Model) {
            return Str::snake(class_basename($this->resource));
        }

        throw new RuntimeException('Unable to determine "type"');
    }

    public function links(Request $request)
    {
        return [
            //
        ];
    }

    /**
     * Set the JSON:API version for the request.
     *
     * @param  string  $version
     * @return $this
     */
    public static function configure(?string $version = null, array $ext = [], array $profile = [], array $meta = [])
    {
        static::$jsonApiInformation = array_filter([
            'version' => $version,
            'ext' => $ext,
            'profile' => $profile,
            'meta' => $meta,
        ]);

        return $this;
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
                // 'attributes' => $this->resolveAttributes($request)->all(),
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
