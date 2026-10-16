<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseAnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AnonymousResourceCollection extends BaseAnonymousResourceCollection
{
    use Concerns\ResolvesJsonApiRequest;

    /**
     * Determine whether the requested relationships have been eager loaded for the collection.
     */
    protected bool $requestedRelationshipsEagerLoaded = false;

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function with($request)
    {
        $this->eagerLoadRequestedRelationships($this->resolveJsonApiRequestFrom($request));

        return array_filter([
            'included' => $this->collection
                ->map(fn ($resource) => $resource->resolveIncludedResourceObjects($request))
                ->flatten(depth: 1)
                ->uniqueStrict('_uniqueKey')
                ->map(fn ($included) => Arr::except($included, ['_uniqueKey']))
                ->values()
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
     * @return array
     */
    #[\Override]
    public function toAttributes(Request $request)
    {
        $this->eagerLoadRequestedRelationships($this->resolveJsonApiRequestFrom($request));

        return $this->collection
            ->map(fn ($resource) => $resource->resolveResourceData($request))
            ->all();
    }

    /**
     * Eager load the relationships requested for the entire collection in a single query.
     *
     * Only the relationship keys declared by the resource are eager loaded; the
     * relationships themselves are still resolved per resource, so closure based
     * relationships keep their per resource behavior. Relationships that were
     * already loaded are left untouched.
     */
    protected function eagerLoadRequestedRelationships(JsonApiRequest $request): void
    {
        if ($this->requestedRelationshipsEagerLoaded) {
            return;
        }

        $this->requestedRelationshipsEagerLoaded = true;

        $relationships = $request->sparseIncluded();

        if (empty($relationships)) {
            return;
        }

        $models = $this->collection
            ->map(fn ($resource) => $resource instanceof JsonResource ? $resource->resource : $resource)
            ->filter(fn ($model) => $model instanceof Model)
            ->values();

        if ($models->isEmpty()) {
            return;
        }

        $resource = rescue(fn () => $models->first()->toResource(), report: false);

        if (! $resource instanceof JsonApiResource) {
            return;
        }

        $relationNames = (new Collection($resource->toRelationships($request)))
            ->transform(fn ($value, $key) => is_int($key) ? new RelationResolver($value) : new RelationResolver($key, $value))
            ->mapWithKeys(fn ($relationResolver) => [$relationResolver->relationName => $relationResolver])
            ->only($relationships)
            ->keys();

        (new EloquentCollection($models->all()))->loadMissing($relationNames->all());
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
        $response->header('Content-Type', 'application/vnd.api+json');
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
        return parent::toResponse($this->resolveJsonApiRequestFrom($request));
    }

    /**
     * Resolve the HTTP request instance from container.
     *
     * @return \Illuminate\Http\Resources\JsonApi\JsonApiRequest
     */
    #[\Override]
    protected function resolveRequestFromContainer()
    {
        return $this->resolveJsonApiRequestFrom(Container::getInstance()->make('request'));
    }
}
