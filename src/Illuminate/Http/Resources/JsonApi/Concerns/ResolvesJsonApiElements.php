<?php

namespace Illuminate\Http\Resources\JsonApi\Concerns;

use Generator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection;
use Illuminate\Http\Resources\JsonApi\Exceptions\ResourceIdentificationException;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Http\Resources\JsonApi\RelationResolver;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use JsonSerializable;
use WeakMap;

trait ResolvesJsonApiElements
{
    /**
     * Determine whether resources respect inclusions and fields from the request.
     */
    protected bool $usesRequestQueryString = true;

    /**
     * Determine whether included relationship for the resource from eager loaded relationship.
     */
    protected bool $includesPreviouslyLoadedRelationships = false;

    /**
     * Cached loaded relationships map.
     *
     * @var array<int, array{0: \Illuminate\Http\Resources\JsonApi\JsonApiResource, 1: string, 2: string, 3: bool}|null
     */
    public $loadedRelationshipsMap;

    /**
     * Cached loaded relationships identifiers.
     */
    protected array $loadedRelationshipIdentifiers = [];

    /**
     * The maximum relationship depth.
     */
    public static int $maxRelationshipDepth = 5;

    /**
     * Specify the maximum relationship depth.
     *
     * @param  non-negative-int  $depth
     */
    public static function maxRelationshipDepth(int $depth): void
    {
        static::$maxRelationshipDepth = max(0, $depth);
    }

    /**
     * Resolves `data` for the resource.
     */
    protected function resolveResourceObject(JsonApiRequest $request): array
    {
        $resourceType = $this->resolveResourceType($request);

        return [
            'id' => $this->resolveResourceIdentifier($request),
            'type' => $resourceType,
            ...(new Collection([
                'attributes' => $this->resolveResourceAttributes($request, $resourceType),
                'relationships' => $this->resolveResourceRelationshipIdentifiers($request),
                'links' => $this->resolveResourceLinks($request),
                'meta' => $this->resolveResourceMetaInformation($request),
            ]))->filter()->map(fn ($value) => (object) $value),
        ];
    }

    /**
     * Resolve the resource's identifier.
     *
     * @return string
     *
     * @throws ResourceIdentificationException
     */
    public function resolveResourceIdentifier(JsonApiRequest $request): string
    {
        if (! is_null($resourceId = $this->toId($request))) {
            return (string) $resourceId;
        }

        if (! ($this->resource instanceof Model || method_exists($this->resource, 'getKey'))) {
            throw ResourceIdentificationException::attemptingToDetermineIdFor($this);
        }

        return (string) $this->resource->getKey();
    }

    /**
     * Resolve the resource's type.
     *
     * @throws ResourceIdentificationException
     */
    public function resolveResourceType(JsonApiRequest $request): string
    {
        if (! is_null($resourceType = $this->toType($request))) {
            return $resourceType;
        }

        if (static::class !== JsonApiResource::class) {
            return Str::of(static::class)->classBasename()->basename('Resource')->snake()->pluralStudly();
        }

        if (! $this->resource instanceof Model) {
            throw ResourceIdentificationException::attemptingToDetermineTypeFor($this);
        }

        $modelClassName = $this->resource::class;

        $morphMap = Relation::getMorphAlias($modelClassName);

        return Str::of(
            $morphMap !== $modelClassName ? $morphMap : class_basename($modelClassName)
        )->snake()->pluralStudly();
    }

    /**
     * Resolve the resource's attributes.
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceAttributes(JsonApiRequest $request, string $resourceType): array
    {
        $data = $this->toAttributes($request);

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        $usesSparseFieldset = $this->usesRequestQueryString && $request->hasSparseFieldset($resourceType);

        $sparseFieldset = $usesSparseFieldset ? $request->sparseFields($resourceType) : [];

        $data = (new Collection($data))
            ->mapWithKeys(fn ($value, $key) => is_int($key) ? [$value => $this->resource->{$value}] : [$key => $value])
            ->when($usesSparseFieldset, fn ($attributes) => $attributes->only($sparseFieldset))
            ->transform(fn ($value) => value($value, $request))
            ->all();

        return $this->filter($data);
    }

    /**
     * Resolves `relationships` for the resource's data object.
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceRelationshipIdentifiers(JsonApiRequest $request): array
    {
        if (! $this->resource instanceof Model) {
            return [];
        }

        $this->compileResourceRelationships($request);

        return [
            ...(new Collection($this->filter($this->loadedRelationshipIdentifiers)))
                ->map(function ($relation) {
                    return ! is_null($relation) ? $relation : ['data' => null];
                })->all(),
        ];
    }

    /**
     * Compile resource relationships.
     */
    protected function compileResourceRelationships(JsonApiRequest $request): void
    {
        if (! is_null($this->loadedRelationshipsMap)) {
            return;
        }

        $sparseIncluded = match (true) {
            $this->includesPreviouslyLoadedRelationships => array_keys($this->resource->getRelations()),
            default => $request->sparseIncluded(),
        };

        $resourceRelationships = (new Collection($this->toRelationships($request)))
            ->transform(fn ($value, $key) => is_int($key) ? new RelationResolver($value) : new RelationResolver($key, $value))
            ->mapWithKeys(fn ($relationResolver) => [$relationResolver->relationName => $relationResolver])
            ->filter(fn ($value, $key) => in_array($key, $sparseIncluded));

        $resourceRelationshipKeys = $resourceRelationships->keys();

        $this->resource->loadMissing($resourceRelationshipKeys->all() ?? []);

        $this->loadedRelationshipsMap = [];

        $this->loadedRelationshipIdentifiers = (new LazyCollection(function () use ($request, $resourceRelationships) {
            foreach ($resourceRelationships as $relationName => $relationResolver) {
                $relatedModels = $relationResolver->handle($this->resource);
                $nestedRelationships = $request->sparseIncluded($relationName);

                if (! is_null($relatedModels) && $this->includesPreviouslyLoadedRelationships === false) {
                    if (! empty($nestedRelationships)) {
                        $this->loadMissingResourceRelationships($relatedModels, $nestedRelationships);
                    }
                }

                yield from $this->compileResourceRelationshipUsingResolver(
                    $request,
                    $this->resource,
                    $relationResolver,
                    $relatedModels,
                    $nestedRelationships,
                );
            }
        }))->all();
    }

    /**
     * Load missing nested relationships for resolved relationship resources.
     */
    protected function loadMissingResourceRelationships(Collection|Model|AnonymousResourceCollection $relatedModels, array $relations): void
    {
        if ($relatedModels instanceof AnonymousResourceCollection) {
            $models = $relatedModels->collection
                ->map(fn ($resource) => $resource instanceof JsonResource ? $resource->resource : $resource)
                ->filter(fn ($resource) => $resource instanceof Model);

            if ($models->isNotEmpty()) {
                (new EloquentCollection($models->all()))->loadMissing($relations);
            }

            return;
        }

        $relatedModels->loadMissing($relations);
    }

    /**
     * Compile resource relations.
     */
    protected function compileResourceRelationshipUsingResolver(
        JsonApiRequest $request,
        mixed $resource,
        RelationResolver $relationResolver,
        Collection|Model|AnonymousResourceCollection|null $relatedModels,
        array $nestedRelationships = [],
        bool $includeLoadedInverseRelationships = true,
    ): Generator {
        $relationName = $relationResolver->relationName;
        $resourceClass = $relationResolver->resourceClass();

        if ($relatedModels instanceof AnonymousResourceCollection) {
            yield from $this->compileResourceRelationshipUsingResourceCollection(
                $request,
                $resource,
                $relationResolver,
                $relatedModels,
                $nestedRelationships,
                $includeLoadedInverseRelationships,
            );

            return;
        }

        // Relationship is a collection of models...
        if ($relatedModels instanceof Collection) {
            $relatedModels = $relatedModels->values();

            if ($relatedModels->isEmpty()) {
                yield $relationName => ['data' => $relatedModels];

                return;
            }

            $relationship = $resource->{$relationName}();

            $isUnique = ! $relationship instanceof BelongsToMany;

            yield $relationName => ['data' => $relatedModels->map(function ($relatedModel) use ($request, $resourceClass, $isUnique, $nestedRelationships, $includeLoadedInverseRelationships) {
                $relatedResource = rescue(fn () => $relatedModel->toResource($resourceClass), new JsonApiResource($relatedModel));

                return transform(
                    [$relatedResource->resolveResourceType($request), $relatedResource->resolveResourceIdentifier($request)],
                    function ($uniqueKey) use ($request, $relatedModel, $relatedResource, $isUnique, $nestedRelationships, $includeLoadedInverseRelationships) {
                        $this->loadedRelationshipsMap[] = [$relatedResource, ...$uniqueKey, $isUnique];

                        $this->compileIncludedNestedRelationshipsMap(
                            $request,
                            $relatedModel,
                            $relatedResource,
                            $nestedRelationships,
                            $includeLoadedInverseRelationships,
                        );

                        return [
                            'id' => $uniqueKey[1],
                            'type' => $uniqueKey[0],
                        ];
                    }
                );
            })->all()];

            return;
        }

        // Relationship is a single model...
        $relatedModel = $relatedModels;

        if (is_null($relatedModel)) {
            yield $relationName => null;

            return;
        } elseif ($relatedModel instanceof Pivot ||
            isset(class_uses_recursive($relatedModel)[AsPivot::class])) {
            yield $relationName => new MissingValue;

            return;
        }

        $relatedResource = rescue(fn () => $relatedModel->toResource($resourceClass), new JsonApiResource($relatedModel));

        yield $relationName => ['data' => transform(
            [$relatedResource->resolveResourceType($request), $relatedResource->resolveResourceIdentifier($request)],
            function ($uniqueKey) use ($relatedModel, $relatedResource, $request, $nestedRelationships, $includeLoadedInverseRelationships) {
                $this->loadedRelationshipsMap[] = [$relatedResource, ...$uniqueKey, true];

                $this->compileIncludedNestedRelationshipsMap(
                    $request,
                    $relatedModel,
                    $relatedResource,
                    $nestedRelationships,
                    $includeLoadedInverseRelationships,
                );

                return [
                    'id' => $uniqueKey[1],
                    'type' => $uniqueKey[0],
                ];
            }
        )];
    }

    /**
     * Compile resource relations from an anonymous resource collection.
     */
    protected function compileResourceRelationshipUsingResourceCollection(
        JsonApiRequest $request,
        mixed $resource,
        RelationResolver $relationResolver,
        AnonymousResourceCollection $relatedResources,
        array $nestedRelationships = [],
        bool $includeLoadedInverseRelationships = true,
    ): Generator {
        $relationName = $relationResolver->relationName;
        $resourceClass = $relationResolver->resourceClass();

        if ($relatedResources->collection->isEmpty()) {
            yield $relationName => ['data' => []];

            return;
        }

        $relationship = $resource->{$relationName}();

        $isUnique = ! $relationship instanceof BelongsToMany;

        yield $relationName => ['data' => $relatedResources->collection->map(function ($relatedResource) use ($request, $resourceClass, $isUnique, $nestedRelationships, $includeLoadedInverseRelationships) {
            $relatedResource = match (true) {
                $relatedResource instanceof JsonApiResource => $relatedResource,
                $relatedResource instanceof JsonResource => new JsonApiResource($relatedResource->resource),
                default => rescue(fn () => $relatedResource->toResource($resourceClass), new JsonApiResource($relatedResource)),
            };

            $relatedModel = $relatedResource->resource;

            return transform(
                [$relatedResource->resolveResourceType($request), $relatedResource->resolveResourceIdentifier($request)],
                function ($uniqueKey) use ($request, $relatedModel, $relatedResource, $isUnique, $nestedRelationships, $includeLoadedInverseRelationships) {
                    $this->loadedRelationshipsMap[] = [$relatedResource, ...$uniqueKey, $isUnique];

                    if ($relatedModel instanceof Model) {
                        $this->compileIncludedNestedRelationshipsMap(
                            $request,
                            $relatedModel,
                            $relatedResource,
                            $nestedRelationships,
                            $includeLoadedInverseRelationships,
                        );
                    }

                    return [
                        'id' => $uniqueKey[1],
                        'type' => $uniqueKey[0],
                    ];
                }
            );
        })->values()->all()];
    }

    /**
     * Compile included relationships map.
     */
    protected function compileIncludedNestedRelationshipsMap(
        JsonApiRequest $request,
        Model $relation,
        JsonApiResource $resource,
        array $nestedRelationships = [],
        bool $includeLoadedInverseRelationships = true,
    ): void {
        if ($this->includesPreviouslyLoadedRelationships) {
            return;
        }

        if ($includeLoadedInverseRelationships) {
            $nestedRelationships = [
                ...$nestedRelationships,
                ...$this->loadedInverseRelationshipsForCurrentResource($relation),
            ];
        }

        if (empty($nestedRelationships)) {
            return;
        }

        $nestedRelationships = (new Collection($nestedRelationships))
            ->mapToGroups(function ($relationship) {
                if (str_contains($relationship, '.')) {
                    [$relation, $with] = explode('.', $relationship, 2);

                    return [$relation => $with];
                }

                return [$relationship => null];
            });

        $resourceRelationships = (new Collection($resource->toRelationships($request)))
            ->transform(fn ($value, $key) => is_int($key) ? new RelationResolver($value) : new RelationResolver($key, $value))
            ->mapWithKeys(fn ($relationResolver) => [$relationResolver->relationName => $relationResolver])
            ->filter(fn ($value, $key) => $nestedRelationships->has($key));

        $resource->loadedRelationshipsMap = [];

        $resource->loadedRelationshipIdentifiers = (new LazyCollection(function () use ($request, $relation, $resource, $resourceRelationships, $nestedRelationships) {
            foreach ($resourceRelationships as $relationName => $relationResolver) {
                $relatedModels = $relationResolver->handle($relation);
                $nested = Collection::wrap($nestedRelationships->get($relationName))->filter()->all();

                if (! is_null($relatedModels) && ! empty($nested)) {
                    $resource->loadMissingResourceRelationships($relatedModels, $nested);
                }

                yield from $resource->compileResourceRelationshipUsingResolver(
                    $request,
                    $relation,
                    $relationResolver,
                    $relatedModels,
                    $nested,
                    false,
                );
            }
        }))->all();
    }

    /**
     * Get loaded inverse relationships that point back to the current resource.
     */
    protected function loadedInverseRelationshipsForCurrentResource(Model $relation): array
    {
        if (! $this->resource instanceof Model) {
            return [];
        }

        return (new Collection($relation->getRelations()))
            ->filter(fn ($related) => $this->referencesCurrentResource($related))
            ->keys()
            ->all();
    }

    /**
     * Determine whether the related value references the current resource model.
     */
    protected function referencesCurrentResource(mixed $related): bool
    {
        if ($related instanceof Model) {
            return $related === $this->resource;
        }

        if ($related instanceof Collection) {
            return $related->contains(fn ($model) => $model === $this->resource);
        }

        return false;
    }

    /**
     * Resolve the included resource object payload.
     */
    protected function resolveIncludedResourceObject(JsonApiRequest $request, JsonApiResource $resource): array
    {
        if ($this->includesPreviouslyLoadedRelationships) {
            return $resource
                ->includePreviouslyLoadedRelationships()
                ->resolve($request);
        }

        if (is_null($resource->loadedRelationshipsMap)) {
            $resource->loadedRelationshipsMap = [];
            $resource->loadedRelationshipIdentifiers = [];
        }

        return $resource->resolve($request);
    }

    /**
     * Resolves `included` for the resource.
     */
    public function resolveIncludedResourceObjects(JsonApiRequest $request): Collection
    {
        if (! $this->resource instanceof Model) {
            return new Collection;
        }

        $this->compileResourceRelationships($request);

        $relations = new Collection;
        $index = 0;

        // Track visited objects by instance + type to prevent infinite loops from circular
        // references created by "chaperone()". We use object instances rather than type
        // and ID for any possible cases like BelongsToMany with different pivot data.
        // We'll track types to allow the same models with different resource types.
        $visitedObjects = new WeakMap;

        $visitedObjects[$this->resource] = [
            $this->resolveResourceType($request) => true,
        ];

        while ($index < count($this->loadedRelationshipsMap)) {
            [$resourceInstance, $type, $id, $isUnique] = $this->loadedRelationshipsMap[$index];

            $underlyingResource = $resourceInstance->resource;

            if (is_object($underlyingResource)) {
                if (isset($visitedObjects[$underlyingResource][$type])) {
                    $index++;
                    continue;
                }

                $visitedObjects[$underlyingResource] ??= [];
                $visitedObjects[$underlyingResource][$type] = true;
            }

            if (! $resourceInstance instanceof JsonApiResource &&
                $resourceInstance instanceof JsonResource) {
                $resourceInstance = new JsonApiResource($resourceInstance->resource);
            }

            $relationsData = $this->resolveIncludedResourceObject($request, $resourceInstance);

            array_push($this->loadedRelationshipsMap, ...($resourceInstance->loadedRelationshipsMap ?? []));

            $relations->push(array_filter([
                'id' => $id,
                'type' => $type,
                '_uniqueKey' => implode(':', $isUnique === true ? [$id, $type] : [$id, $type, (string) Str::random()]),
                'attributes' => Arr::get($relationsData, 'data.attributes'),
                'relationships' => Arr::get($relationsData, 'data.relationships'),
                'links' => Arr::get($relationsData, 'data.links'),
                'meta' => Arr::get($relationsData, 'data.meta'),
            ]));

            $index++;
        }

        return $relations;
    }

    /**
     * Resolve the links for the resource.
     *
     * @return array<string, mixed>
     */
    protected function resolveResourceLinks(JsonApiRequest $request): array
    {
        return $this->toLinks($request);
    }

    /**
     * Resolve the meta information for the resource.
     *
     * @return array<string, mixed>
     */
    protected function resolveResourceMetaInformation(JsonApiRequest $request): array
    {
        return $this->toMeta($request);
    }

    /**
     * Indicate that relationship loading should respect the request's "includes" query string.
     *
     * @return $this
     */
    public function respectFieldsAndIncludesInQueryString(bool $value = true)
    {
        $this->usesRequestQueryString = $value;

        return $this;
    }

    /**
     * Indicate that relationship loading should not rely on the request's "includes" query string.
     *
     * @return $this
     */
    public function ignoreFieldsAndIncludesInQueryString()
    {
        return $this->respectFieldsAndIncludesInQueryString(false);
    }

    /**
     * Determine relationship should include loaded relationships.
     *
     * @return $this
     */
    public function includePreviouslyLoadedRelationships()
    {
        $this->includesPreviouslyLoadedRelationships = true;

        return $this;
    }
}
