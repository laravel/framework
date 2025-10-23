<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

abstract class JsonApiResource extends JsonResource
{

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
            'id' => $this->resolveId($request),
            'type' => $this->resolveType($request),
            ...(new Collection([
                'attributes' => $this->requestedAttributes($request)->all(),
                'relationships' => $this->requestedRelationshipsAsIdentifiers($request)->all(),
                'links' => self::parseLinks(array_merge($this->toLinks($request), $this->links)),
                'meta' => array_merge($this->toMeta($request), $this->meta),
            ]))->filter()->map(fn ($value) => (object) $value),
        ];
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

}
