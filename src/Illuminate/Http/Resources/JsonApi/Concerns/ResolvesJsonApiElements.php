<?php

namespace Illuminate\Http\Resources\JsonApi\Concerns;

use Generator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\AsPivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
use JsonSerializable;

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
     * Cached loaded relationships identifers.
     */
    protected array $loadedRelationshipIdentifiers = [];

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
     * @return string|int
     *
     * @throws ResourceIdentificationException
     */
    public function resolveResourceIdentifier(JsonApiRequest $request): string
    {
        if (! is_null($resourceId = $this->toId($request))) {
            return $resourceId;
        }

        if (! $this->resource instanceof Model) {
            throw ResourceIdentificationException::attemptingToDetermineIdFor($this);
        }

        return static::resourceIdFromModel($this->resource);
    }

    /**
     * Resolve the resource's type.
     *
     *
     * @throws ResourceIdentificationException
     */
    public function resolveResourceType(JsonApiRequest $request): string
    {
        if (! is_null($resourceType = $this->toType($request))) {
            return $resourceType;
        }

        if (! $this->resource instanceof Model) {
            throw ResourceIdentificationException::attemptingToDetermineTypeFor($this);
        }

        return static::resourceTypeFromModel($this->resource);
    }

    /**
     * Resolve the resource's attributes.
     *
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

        $sparseFieldset = match ($this->usesRequestQueryString) {
            true => $request->sparseFields($resourceType),
            default => [],
        };

        $data = (new Collection($data))
            ->mapWithKeys(fn ($value, $key) => is_int($key) ? [$value => $this->resource->{$value}] : [$key => $value])
            ->when(! empty($sparseFieldset), fn ($attributes) => $attributes->only($sparseFieldset))
            ->reject(fn ($value, $key) => $key === $this->resource->getKey())
            ->transform(fn ($value) => value($value, $request))
            ->all();

        return $this->filter($data);
    }

    /**
     * Resolves `relationships` for the resource's data object.
     *
     * @return string|int
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
                $relatedResourceClass = $relationResolver->resourceClass();

                if (! is_null($relatedModels)) {
                    $relatedModels->loadMissing($request->sparseIncluded($relationName));
                }

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
        mixed $resource,
        JsonApiRequest $request,
        RelationResolver $relationResolver,
        Collection|Model|null $relatedModels
    ): Generator {
        $relationName = $relationResolver->relationName;
        $resourceClass = $relationResolver->resourceClass();

        // Relationship is a collection of models...
        if ($relatedModels instanceof Collection) {
            $relatedModels = $relatedModels->values();

            if ($relatedModels->isEmpty()) {
                yield $relationName => ['data' => $relatedModels];

                return;
            }

            $relationship = $resource->{$relationName}();

            $isUnique = ! $relationship instanceof BelongsToMany;

            yield $relationName => ['data' => $relatedModels->map(function ($relatedModel) use ($request, $resourceClass, $isUnique) {
                $relatedResource = rescue(fn () => $relatedModel->toResource($resourceClass), new JsonApiResource($relatedModel));

                return transform(
                    [$relatedResource->resolveResourceType($request), $relatedResource->resolveResourceIdentifier($request)],
                    function ($uniqueKey) use ($request, $relatedModel, $relatedResource, $isUnique) {
                        $this->loadedRelationshipsMap[] = [$relatedResource, ...$uniqueKey, $isUnique];

                        $this->compileIncludedNestedRelationshipsMap($request, $relatedModel, $relatedResource);

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
            in_array(AsPivot::class, class_uses_recursive($relatedModel), true)) {
            yield $relationName => new MissingValue;

            return;
        }

        $relatedResource = rescue(fn () => $relatedModel->toResource($resourceClass), new JsonApiResource($relatedModel));

        yield $relationName => ['data' => transform(
            [$relatedResource->resolveResourceType($request), $relatedResource->resolveResourceIdentifier($request)],
            function ($uniqueKey) use ($relatedModel, $relatedResource, $request) {
                $this->loadedRelationshipsMap[] = [$relatedResource, ...$uniqueKey, true];

                $this->compileIncludedNestedRelationshipsMap($request, $relatedModel, $relatedResource);

                return [
                    'id' => $uniqueKey[1],
                    'type' => $uniqueKey[0],
                ];
            }
        )];
    }

    /**
     * Compile included relationships map.
     */
    protected function compileIncludedNestedRelationshipsMap(JsonApiRequest $request, Model $relation, JsonApiResource $resource): void
    {
        (new Collection($resource->toRelationships($request)))
            ->transform(fn ($value, $key) => is_int($key) ? new RelationResolver($value) : new RelationResolver($key, $value))
            ->mapWithKeys(fn ($relationResolver) => [$relationResolver->relationName => $relationResolver])
            ->filter(fn ($value, $key) => in_array($key, array_keys($relation->getRelations())))
            ->each(function ($relationResolver, $key) use ($relation, $request) {
                $this->compileResourceRelationshipUsingResolver($relation, $relationResolver, $relation->getRelation($key), $request);
            });
    }

    /**
     * Resolves `included` for the resource.
     */
    public function resolveIncludedResources(JsonApiRequest $request): array
    {
        if (! $this->resource instanceof Model) {
            return [];
        }

        $this->compileResourceRelationships($request);

        $relations = new Collection;

        $index = 0;

        while ($index < count($this->loadedRelationshipsMap)) {
            [$resourceInstance, $type, $id, $isUnique] = $this->loadedRelationshipsMap[$index];

            if (! $resourceInstance instanceof JsonApiResource &&
                $resourceInstance instanceof JsonResource) {
                $resourceInstance = new JsonApiResource($resourceInstance->resource);
            }

            $relationsData = $resourceInstance
                ->withoutRequestQueryString()
                ->includePreviouslyLoadedRelationships()
                ->resolve($request);

            array_push($this->loadedRelationshipsMap, ...$resourceInstance->loadedRelationshipsMap);

            $relations->push(array_filter([
                'id' => $id,
                'type' => $type,
                '_uniqueKey' => $isUnique === true ? [$id, $type] : [$id, $type, (string) Str::random()],
                'attributes' => Arr::get($relationsData, 'data.attributes'),
                'relationships' => Arr::get($relationsData, 'data.relationships'),
                'links' => Arr::get($relationsData, 'data.links'),
                'meta' => Arr::get($relationsData, 'data.meta'),
            ]));

            $index++;
        }

        return $relations->uniqueStrict(fn ($relation) => $relation['_uniqueKey'])
            ->map(fn ($relation) => Arr::except($relation, ['_uniqueKey']))
            ->all();
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
     * Get the resource ID from the given Eloquent model.
     */
    public static function resourceIdFromModel(Model $model): string
    {
        return $model->getKey();
    }

    /**
     * Get the resource type from the given Eloquent model.
     */
    public static function resourceTypeFromModel(Model $model): string
    {
        $modelClassName = $model::class;

        $morphMap = Relation::getMorphAlias($modelClassName);

        return static::normalizeResourceType(
            $morphMap !== $modelClassName ? $morphMap : class_basename($modelClassName)
        );
    }

    /**
     * Indicate that relationship loading should respect the request's "includes" query string.
     *
     * @return $this
     */
    public function withRequestQueryString(bool $value = true)
    {
        $this->usesRequestQueryString = $value;

        return $this;
    }

    /**
     * Indicate that relationship loading should not rely on the request's "includes" query string.
     *
     * @return $this
     */
    public function withoutRequestQueryString()
    {
        return $this->withRequestQueryString(false);
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

    /**
     * Normalize the resource type.
     */
    public static function normalizeResourceType(string $value): string
    {
        return Str::of($value)->snake()->pluralStudly();
    }
}
