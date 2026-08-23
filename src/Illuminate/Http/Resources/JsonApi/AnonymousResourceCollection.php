<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseAnonymousResourceCollection;
use Illuminate\Support\Arr;

class AnonymousResourceCollection extends BaseAnonymousResourceCollection
{
    use Concerns\ResolvesJsonApiRequest;

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
     * Eager load the requested relationships across every resource in the collection.
     */
    protected function eagerLoadRequestedRelationships(JsonApiRequest $request): void
    {
        if (empty($request->sparseIncluded())) {
            return;
        }

        $models = new Collection(
            $this->collection
                ->pluck('resource')
                ->filter(fn ($model) => $model instanceof Model)
                ->all()
        );

        if ($models->isEmpty()) {
            return;
        }

        $relationships = $this->collection
            ->flatMap(fn ($resource) => $resource->resolveRelationshipsToEagerLoad($request))
            ->unique()
            ->flatMap(fn ($relationship) => $this->expandRequestedRelationship($request, $relationship))
            ->all();

        $models->loadMissing($relationships);
    }

    /**
     * Expand a relationship into the nested relationships requested for it.
     *
     * @return array<int, string>
     */
    protected function expandRequestedRelationship(JsonApiRequest $request, string $relationship): array
    {
        $nested = $request->sparseIncluded($relationship);

        return empty($nested)
            ? [$relationship]
            : array_map(fn ($path) => $relationship.'.'.$path, $nested);
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
