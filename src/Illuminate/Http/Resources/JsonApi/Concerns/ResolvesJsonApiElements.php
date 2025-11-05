<?php

namespace Illuminate\Http\Resources\JsonApi\Concerns;

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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use WeakMap;

trait ResolvesJsonApiElements
{
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
     * Resolves `data` for the resource.
     */
    public function resolveResourceData(JsonApiRequest $request): array
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
    protected function resolveResourceIdentifier(JsonApiRequest $request): string
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
    protected function resolveResourceType(JsonApiRequest $request): string
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

        $sparseFieldsets = $request->sparseFields($resourceType);

        $data = (new Collection($data))
            ->mapWithKeys(fn ($value, $key) => is_int($key) ? [$value => $this->resource->{$value}] : [$key => $value])
            ->when(! empty($sparseFieldsets), fn ($attributes) => $attributes->only($sparseFieldsets))
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
            ...$this->loadedRelationshipIdentifiers,
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

        $resourceRelationships = $this->toRelationships($request);

        $resourceRelationshipKeys = array_is_list($resourceRelationships)
            ? $resourceRelationships
            : array_flip(array_keys($resourceRelationships));

        $this->resource->loadMissing($resourceRelationshipKeys);

        $this->loadedRelationshipsMap = new WeakMap;

        $this->loadedRelationshipIdentifiers = (new Collection(
            array_is_list($resourceRelationships)
                ? array_intersect_key($this->resource->getRelations(), $resourceRelationshipKeys)
                : $resourceRelationships
        ))->mapWithKeys(function ($relations, $key) {
            $relations = value($relations);

            if ($relations instanceof Collection) {
                $relations = $relations->values();

                if ($relations->isEmpty()) {
                    return [$key => ['data' => $relations]];
                }

                $relationship = $this->resource->{$key}();

                $isUnique = ! $relationship instanceof BelongsToMany;

                $key = static::resourceTypeFromModel($relations->first());

                return [$key => ['data' => $relations->map(function ($relation) use ($key, $isUnique) {
                    return transform([$key, static::resourceIdFromModel($relation)], function ($uniqueKey) use ($relation, $isUnique) {
                        $this->loadedRelationshipsMap[$relation] = [...$uniqueKey, $isUnique];

                        return ['id' => $uniqueKey[1], 'type' => $uniqueKey[0]];
                    });
                })]];
            }

            if (is_null($relations) ||
                $relations instanceof Pivot ||
                in_array(AsPivot::class, class_uses_recursive($relations), true)) {
                return [$key => null];
            }

            return [$key => ['data' => [transform(
                [static::resourceTypeFromModel($relations), static::resourceIdFromModel($relations)],
                function ($uniqueKey) use ($relations) {
                    $this->loadedRelationshipsMap[$relations] = [...$uniqueKey, true];

                    return ['id' => $uniqueKey[1], 'type' => $uniqueKey[0]];
                }
            )]]];
        })->filter()->all();
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

        foreach ($this->loadedRelationshipsMap as $relation => $value) {
            $resourceInstance = rescue(fn () => $relation->toResource(), new JsonApiResource($relation), false);

            if (! $resourceInstance instanceof JsonApiResource &&
                $resourceInstance instanceof JsonResource) {
                $resourceInstance = new JsonApiResource($resourceInstance->resource);
            }

            [$type, $id, $isUnique] = $value;

            $relations->push([
                'id' => $id,
                'type' => $type,
                '_uniqueKey' => $isUnique === true ? [$id, $type] : [$id, $type, (string) Str::random()],
                'attributes' => Arr::get($resourceInstance->resolve($request), 'data.attributes', []),
            ]);
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
    protected static function resourceIdFromModel(Model $model): string
    {
        return $model->getKey();
    }

    /**
     * Get the resource type from the given Eloquent model.
     */
    protected static function resourceTypeFromModel(Model $model): string
    {
        $modelClassName = $model::class;

        $morphMap = Relation::getMorphAlias($modelClassName);

        return Str::of(
            $morphMap !== $modelClassName ? $morphMap : class_basename($modelClassName)
        )->snake()->pluralStudly();
    }
}
