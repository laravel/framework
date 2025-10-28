<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class JsonApiResourceCollection extends AnonymousResourceCollection
{
    /**
     * @param  (callable(\Illuminate\Http\Resources\JsonApi\JsonApiResource): \Illuminate\Http\Resources\JsonApi\JsonApiResource)  $callback
     * @return $this
     */
    public function map(callable $callback)
    {
        $this->collection = $this->collection->map($callback);

        return $this;
    }

    /**
     * @return RelationshipObject
     */
    public function toResourceLink(Request $request)
    {
        return RelationshipObject::toMany($this->resolveResourceIdentifiers($request)->all());
    }

    /**
     * @return Collection<int, ResourceIdentifier>
     */
    private function resolveResourceIdentifiers(Request $request)
    {
        return $this->collection
            ->uniqueStrict(fn (JsonApiResource $resource): array => $resource->uniqueKey($request))
            ->map(fn (JsonApiResource $resource): ResourceIdentifier => $resource->resolveResourceIdentifier($request));
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array{included?: array<int, \Illuminate\Http\Resources\JsonApi\JsonApiResource>, jsonapi?: ServerImplementation}
     */
    public function with($request)
    {
        return [
            ...($included = $this->collection
                ->map(fn (JsonApiResource $resource): Collection => $resource->included($request))
                ->flatten()
                ->uniqueStrict(fn (JsonApiResource $resource): array => $resource->uniqueKey($request))
                ->values()
                ->all()) ? ['included' => $included] : [],
            ...($implementation = JsonApiResource::$jsonApiInformation) // @TODO
                ? ['jsonapi' => $implementation] : [],
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
        return tap(parent::toResponse($request)->header('Content-type', 'application/vnd.api+json'), $this->flush(...));
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  array<array-key, mixed>  $paginated
     * @param  array{links: array<string, ?string>}  $default
     * @return array{links: array<string, string>}
     */
    public function paginationInformation(Request $request, array $paginated, array $default)
    {
        if (isset($default['links'])) {
            $default['links'] = array_filter($default['links'], fn (?string $link): bool => $link !== null);
        }

        if (isset($default['meta']['links'])) {
            $default['meta']['links'] = array_map(
                function (array $link): array {
                    $link['label'] = (string) $link['label'];

                    return $link;
                },
                $default['meta']['links']
            );
        }

        return $default;
    }

    /**
     * Set include prefix to resources.
     *
     * @internal
     *
     * @param  string  $prefix
     * @return $this
     */
    public function withIncludePrefix(string $prefix)
    {
        $this->collection->each(fn (JsonApiResource $resource): JsonApiResource => $resource->withIncludePrefix($prefix));

        return $this;
    }

    /**
     * Get included resources.
     *
     * @internal
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, \Illuminate\Http\Resources\JsonApi\JsonApiResource>>
     */
    public function included(Request $request)
    {
        return $this->collection->map(fn (JsonApiResource $resource): Collection => $resource->included($request));
    }

    /**
     * Get the includable collection.
     *
     * @internal
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Http\Resources\JsonApi\JsonApiResource>
     */
    public function includable()
    {
        return $this->collection;
    }

    /**
     * Flush resource collection states.
     *
     * @internal
     *
     * @return void
     */
    public function flush(): void
    {
        $this->collection->each(fn (JsonApiResource $resource) => $resource->flush());
    }
}
