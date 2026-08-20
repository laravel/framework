<?php

namespace Illuminate\Http\Resources\JsonApi;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseAnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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
        $this->loadMissingResourceRelationships($request = $this->resolveJsonApiRequestFrom($request));

        return $this->collection
            ->map(fn ($resource) => $resource->resolveResourceData($request))
            ->all();
    }

    /**
     * Load the requested resource relationships for the collection.
     */
    protected function loadMissingResourceRelationships(JsonApiRequest $request): void
    {
        if (empty($requestedRelationships = $request->sparseIncluded())) {
            return;
        }

        $loadGroups = [];

        foreach ($this->collection as $resource) {
            if (! $resource instanceof JsonApiResource || ! $resource->resource instanceof Model) {
                continue;
            }

            $relationships = [];

            foreach (new Collection($resource->toRelationships($request)) as $key => $value) {
                $relationship = is_int($key) ? $value : $key;

                if (! is_string($relationship) || ! in_array($relationship, $requestedRelationships, true)) {
                    continue;
                }

                $nestedRelationships = $request->sparseIncluded($relationship);
                $paths = $value instanceof Closure || empty($nestedRelationships)
                    ? [$relationship]
                    : array_map(fn ($nestedRelationship) => $relationship.'.'.$nestedRelationship, $nestedRelationships);

                foreach ($paths as $path) {
                    $relationships[$path] = true;
                }
            }

            if (empty($relationships)) {
                continue;
            }

            $relationships = array_keys($relationships);
            sort($relationships);

            $groupKey = $resource->resource::class."\0".implode("\0", $relationships);
            $loadGroups[$groupKey]['models'][] = $resource->resource;
            $loadGroups[$groupKey]['relationships'] = $relationships;
        }

        foreach ($loadGroups as $loadGroup) {
            $loadGroup['models'][0]
                ->newCollection($loadGroup['models'])
                ->loadMissing($loadGroup['relationships']);
        }
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
