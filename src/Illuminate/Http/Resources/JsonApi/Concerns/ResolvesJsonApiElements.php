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
     * Determine whether resource use included and fields from the request object.
     */
    protected bool $usesRequestQueryString = true;

    /**
     * Determine whether included relationship for the resource from eager loaded relationship.
     */
    protected bool $usesIncludedFromLoadedRelationships = false;

    /**
     * Cached loaded relationships map.
     *
     * @var \WeakMap|null
     */
    protected $loadedRelationshipsMap;

    /**
     * Cached loaded relationships identifers.
     */
    protected array $loadedRelationshipIdentifiers = [];

    /**
     * Determine relationships should rely on request's query string.
     *
     * @return $this
     */
    public function withRequestQueryString(bool $value = true)
    {
        $this->usesRequestQueryString = $value;

        return $this;
    }

    /**
     * Determine relationships should not be relied on request's query string.
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
    public function withIncludedFromLoadedRelationships()
    {
        $this->usesIncludedFromLoadedRelationships = true;

        return $this;
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
        if ($this->loadedRelationshipsMap instanceof WeakMap) {
            return;
        }

        $sparseIncluded = match (true) {
            $this->usesIncludedFromLoadedRelationships => array_keys($this->resource->getRelations()),
            default => $request->sparseIncluded(),
        };

        $resourceRelationships = (new Collection($this->toRelationships($request)))
            ->mapWithKeys(function ($value, $key) {
                $relationResolver = is_int($key) ? new RelationResolver($value) : new RelationResolver($key, $value);

                return [$relationResolver->relationName => $relationResolver];
            })->filter(fn ($value, $key) => in_array($key, $sparseIncluded));

        $resourceRelationshipKeys = $resourceRelationships->keys();

        $this->resource->loadMissing($resourceRelationshipKeys->all() ?? []);

        $this->loadedRelationshipsMap = new WeakMap;

        $this->loadedRelationshipIdentifiers = (new LazyCollection(function () use ($request, $resourceRelationships) {
            foreach ($resourceRelationships as $relationName => $relationResolver) {
                $relatedModels = $relationResolver->handle($this->resource);
                $relatedResourceClass = $relationResolver->resourceClass();

                if (! is_null($relatedModels)) {
                    $relatedModels->loadMissing($request->sparseIncluded($relationName));
                }

                yield from $this->compileResourceRelationshipUsingResolver(
                    $relationResolver,
                    $relatedModels,
                    $request
                );
            }
        }))->all();

        dump($this->loadedRelationshipIdentifiers);
    }

    /**
     * Compile resource relations.
     */
    protected function compileResourceRelationshipUsingResolver(
        RelationResolver $relationResolver,
        Collection|Model|null $relatedModels,
        JsonApiRequest $request
    ): Generator {
        $relationName = $relationResolver->relationName;
        $resourceClass = $relationResolver->resourceClass();

        // Relationship is a collection of models...
        if ($relatedModels instanceof Collection) {
            $relatedResources = rescue(
                fn () => $relatedModels->toResourceCollection($resourceClass),
                new AnonymousResourceCollection($relatedModels->values(), JsonApiResource::class)
            );

            if ($relatedModels->isEmpty()) {
                yield $relationName => ['data' => $relatedModels];

                return;
            }

            $relationship = $this->resource->{$relationName}();
            $isUnique = ! $relationship instanceof BelongsToMany;

            yield $relationName => ['data' => $relatedResources->collection->map(function ($resource) use ($request, $isUnique) {
                return transform(
                    [$resource->resolveResourceType($request), $resource->resolveResourceIdentifier($request)],
                    function ($uniqueKey) use ($resource, $isUnique) {
                        $this->loadedRelationshipsMap[$resource] = [...$uniqueKey, $isUnique];

                        return [
                            'id' => $uniqueKey[1],
                            'type' => $uniqueKey[0],
                        ];
                    }
                );
            })];

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
            function ($uniqueKey) use ($relatedResource) {
                $this->loadedRelationshipsMap[$relatedResource] = [...$uniqueKey, true];

                return [
                    'id' => $uniqueKey[1],
                    'type' => $uniqueKey[0],
                ];
            }
        )];
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

        foreach ($this->loadedRelationshipsMap as $resourceInstance => $value) {
            [$type, $id, $isUnique] = $value;

            if (! $resourceInstance instanceof JsonApiResource &&
                $resourceInstance instanceof JsonResource) {
                $resourceInstance = new JsonApiResource($resourceInstance->resource);
            }

            $relationsData = $resourceInstance->withoutRequestQueryString()->withIncludedFromLoadedRelationships()->resolve($request);

            $relations->push(array_filter([
                'id' => $id,
                'type' => $type,
                '_uniqueKey' => $isUnique === true ? [$id, $type] : [$id, $type, (string) Str::random()],
                'attributes' => Arr::get($relationsData, 'data.attributes'),
                'relationships' => Arr::get($relationsData, 'data.relationships'),
                'links' => Arr::get($relationsData, 'data.links'),
                'meta' => Arr::get($relationsData, 'data.meta'),
            ]));
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
     * Normalize the resource type.
     */
    public static function normalizeResourceType(string $value): string
    {
        return Str::of($value)->snake()->pluralStudly();
    }
}
