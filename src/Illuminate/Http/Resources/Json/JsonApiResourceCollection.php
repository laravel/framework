<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Http\Request;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    /**
     * @param  Request  $request
     * @return array{included?: array<int, JsonApiResource>, jsonapi: ServerImplementation}
     */
    public function with($request)
    {
        return array_filter([
            'included' => $this->collection
                ->map(fn ($resource) => $resource->resolveResourceIncluded($request))
                ->flatten(depth: 1)
                ->uniqueStrict(fn ($relation): array => [$relation['id'], $relation['type']])
                ->all(),
            ...($implementation = JsonApiResource::$jsonApiInformation)
                ? ['jsonapi' => $implementation]
                : [],
        ]);
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        return $this->collection
            ->map(fn ($resource) => $resource->resolveResourceData($request))
            ->all();
    }
}
