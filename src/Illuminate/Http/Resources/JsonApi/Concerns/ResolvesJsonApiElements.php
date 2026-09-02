<?php

namespace Illuminate\Http\Resources\JsonApi\Concerns;

use Generator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\JsonApi\Exceptions\ResourceIdentificationException;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Http\Resources\JsonApi\RelationResolver;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
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
     * The relationships requested relative to this included resource.
     *
     * @var array<int, string>|null
     */
    protected ?array $requestedRelationships = null;

    /**
     * Cached loaded relationships map.
     *
     * @var array<int, array{0: \Illuminate\Http\Resources\JsonApi\JsonApiResource, 1: string, 2: string, 3: bool}>|null
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
            return (new Stringable(static::class))->classBasename()->basename('Resource')->snake()->pluralStudly();
        }

        if (! $this->resource instanceof Model) {
            throw ResourceIdentificationException::attemptingToDetermineTypeFor($this);
        }

        $modelClassName = $this->resource::class;

        $morphMap = Relation::getMorphAlias($modelClassName);

        return (new Stringable(
            $morphMap !== $modelClassName ? $morphMap : class_basename($modelClassName)
        ))->snake()->pluralStudly();
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

        $sparseIncluded = $this->requestedResourceRelationships($request);

        $resourceRelationships = (new Collection($this->toRelationships($request)))
            ->transform(fn ($value, $key) => is_int($key) ? new RelationResolver($value) : new RelationResolver($key, $value))
            ->mapWithKeys(fn ($relationResolver) => [$relationResolver->relationName => $relationResolver])
            ->only($sparseIncluded);

        $resourceRelationshipKeys = $resourceRelationships->keys();

        $this->resource->loadMissing($resourceRelationshipKeys->all() ?? []);

        $this->loadedRelationshipsMap = [];

        $this->loadedRelationshipIdentifiers = (new LazyCollection(function () use ($request, $resourceRelationships) {
            foreach ($resourceRelationships as $relationName => $relationResolver) {
                $relatedModels = $relationResolver->handle($this->resource);

                yield from $this->compileResourceRelationshipUsingResolver(
                    $request,
                    $this->resource,
                    $relationResolver,
                    $relatedModels,
                );
            }
        }))->all();
    }

    /**
     * Compile resource relations.
     */
    protected function compileResourceRelationshipUsingResolver(
        JsonApiRequest $request,
        mixed $resource,
        RelationResolver $relationResolver,
        Collection|Model|null $relatedModels
    ): Generator {
        $relationName = $relationResolver->relationName;
        $resourceClass = $relationResolver->resourceClass();
        $requestedRelationships = $this->requestedResourceRelationships($request, $relationName);

        // Relationship is a collection of models...
        if ($relatedModels instanceof Collection) {
            $relatedModels = $relatedModels->values();

            if ($relatedModels->isEmpty()) {
                yield $relationName => ['data' => $relatedModels];

                return;
            }

            $relationship = $resource->{$relationName}();

            $isUnique = ! $relationship instanceof BelongsToMany;

            yield $relationName => ['data' => $relatedModels->map(function ($relatedModel) use ($request, $resourceClass, $isUnique, $requestedRelationships) {
                $relatedResource = rescue(fn () => $relatedModel->toResource($resourceClass), new JsonApiResource($relatedModel));

                $relatedResource->requestedRelationships = $requestedRelationships;

                if (! empty($requestedRelationships)) {
                    $relatedResource->includePreviouslyLoadedRelationships()->compileResourceRelationships($request);
                }

                return transform(
                    [$relatedResource->resolveResourceType($request), $relatedResource->resolveResourceIdentifier($request)],
                    function ($uniqueKey) use ($relatedResource, $isUnique) {
                        $this->loadedRelationshipsMap[] = [$relatedResource, ...$uniqueKey, $isUnique];

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

        $relatedResource->requestedRelationships = $requestedRelationships;

        if (! empty($requestedRelationships)) {
            $relatedResource->includePreviouslyLoadedRelationships()->compileResourceRelationships($request);
        }

        yield $relationName => ['data' => transform(
            [$relatedResource->resolveResourceType($request), $relatedResource->resolveResourceIdentifier($request)],
            function ($uniqueKey) use ($relatedResource) {
                $this->loadedRelationshipsMap[] = [$relatedResource, ...$uniqueKey, true];

                return [
                    'id' => $uniqueKey[1],
                    'type' => $uniqueKey[0],
                ];
            }
        )];
    }

    /**
     * Get the requested relationships for this resource or one of its relationships.
     */
    protected function requestedResourceRelationships(JsonApiRequest $request, ?string $relationName = null): array
    {
        if (is_null($this->requestedRelationships)) {
            if ($this->includesPreviouslyLoadedRelationships) {
                return is_null($relationName) ? array_keys($this->resource->getRelations()) : [];
            }

            return $request->sparseIncluded($relationName) ?? [];
        }

        if (is_null($relationName)) {
            $requested = (new Collection($this->requestedRelationships))
                ->map(fn ($relationship) => explode('.', $relationship, 2)[0]);

            if ($this->includesPreviouslyLoadedRelationships) {
                $requested->push(...array_keys($this->resource->getRelations()));
            }

            return $requested->unique()->values()->all();
        }

        return (new Collection($this->requestedRelationships))
            ->filter(fn ($relationship) => str_starts_with($relationship, $relationName.'.'))
            ->map(fn ($relationship) => substr($relationship, strlen($relationName) + 1))
            ->values()
            ->all();
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

            $relationsData = $resourceInstance
                ->includePreviouslyLoadedRelationships()
                ->resolve($request);

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
