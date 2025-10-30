<?php

namespace Illuminate\Http\Resources\JsonApi\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonApiResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use RuntimeException;
use WeakMap;

trait ResolvesJsonApiSpecifications
{
    /**
     * @var \WeakMap|null
     */
    protected $cachedLoadedRelationshipsMap;

    /**
     * @var array
     */
    protected array $cachedLoadedRelationshipsIdentifier = [];

    /**
     * Resolves `attributes` for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|int
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceAttributes(Request $request): array
    {
        $data = $this->toArray($request);

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        $data = (new Collection($data))
            ->transform(fn ($value) => value($value, $request))
            ->all();

        return $this->filter($data);
    }

    public function resolveResourceData(Request $request): array
    {
        return [
            'id' => $this->resolveResourceIdentifier($request),
            'type' => $this->resolveResourceType($request),
            ...(new Collection([
                'attributes' => $this->resolveResourceAttributes($request),
                'relationships' => $this->resolveResourceRelationshipsIdentifiers($request),
                'links' => $this->resolveResourceLinks($request),
                'meta' => $this->resolveMetaInformations($request),
            ]))->filter()->map(fn ($value) => (object) $value),
        ];
    }

    protected function resolveResourceRelationships(Request $request): void
    {
        if ($this->cachedLoadedRelationships instanceof WeakMap) {
            return;
        }

        $this->cachedLoadedRelationshipsMap = new WeakMap;

        $this->cachedLoadedRelationshipsIdentifier = (new Collection($this->resource->getRelations()))
            ->mapWithKeys(function ($relations, $key) {
                if ($relations instanceof Collection) {
                    if ($relations->isEmpty()) {
                        return [$key => $relations];
                    }

                    $key = static::getResourceTypeFromEloquent($relations->first());

                    return [$key => $relations->map(function ($relation) use ($key) {
                        return transform([$key, static::getResourceIdFromEloquent($relation)], function ($uniqueKey) use ($relation) {
                            $this->cachedLoadedRelationshipsMap[$relation] = $uniqueKey;

                            return ['id' => $uniqueKey[1], 'type' => $uniqueKey[0]];
                        });
                    })];
                }

                return transform(
                    [static::getResourceTypeFromEloquent($relation), static::getResourceIdFromEloquent($relation)],
                    function ($uniqueKey) {
                        $this->cachedLoadedRelationshipsMap[$relation] = $uniqueKey;

                        return ['id' => $uniqueKey[1], 'type' => $uniqueKey[0]];
                    }
                );
            })->all();
    }

    protected function resolveResourceRelationshipsIdentifiers(Request $request): array
    {
        if (! $this->resource instanceof Model) {
            return [];
        }

        $this->resolveResourceRelationships($request);

        return [
            'data' => $this->cachedLoadedRelationshipsIdentifier,
        ];
    }

    public function resolveResourceIncluded(Request $request): array
    {
        $relations = new Collection();

        foreach ($this->cachedLoadedRelationshipsMap as $relation => $uniqueKey) {
            $resource = rescue(fn () => $relation->toResource(), new JsonApiResource($relation), false);
            $relations->push([
                'id' => $uniqueKey[1],
                'type' => $uniqueKey[0],
                'attributes' => $resource->toArray($request),
            ]);
        }

        return $relations->uniqueStrict(fn ($relation): array => [$relation['id'], $relation['type']])->all();
    }

    /**
     * Resolves `id` for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|int
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceIdentifier(Request $request): string
    {
        if ($this->resource instanceof Model) {
            return static::getResourceIdFromEloquent($this->resource);
        }

        throw new RuntimeException('Unable to determine "type"');
    }

    /**
     * Resolves `type` for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function resolveResourceType(Request $request): string
    {
        if ($this->resource instanceof Model) {
            return static::getResourceTypeFromEloquent($this->resource);
        }

        throw new RuntimeException('Unable to determine "type"');
    }

    /**
     * Get unique key for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array{0: string, 1: string}
     */
    public function uniqueResourceKey(Request $request): array
    {
        return [$this->resolveResourceType($request), $this->resolveResourceIdentifier($request)];
    }

    /**
     * Resolves `links` object for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    protected function resolveResourceLinks(Request $request): array
    {
        return [];
    }

    /**
     * Resolves `meta` object for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    protected function resolveMetaInformations(Request $request): array
    {
        return $this->meta($request);
    }

    /**
     * Get expected resource ID from eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    protected static function getResourceIdFromEloquent(Model $model): string
    {
        return $model->getKey();
    }

    /**
     * Get expected resource type from eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    protected static function getResourceTypeFromEloquent(Model $model): string
    {
        $modelClassName = $model::class;
        $morphMap = Relation::getMorphAlias($modelClassName);

        $modelBaseName = $morphMap !== $modelClassName ? $morphMap : class_basename($modelClassName);

        return Str::of($modelBaseName)->snake()->pluralStudly();
    }
}
