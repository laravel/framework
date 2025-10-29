<?php

namespace Illuminate\Http\Resources\Json\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
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
    protected $cachedLoadedRelationships;

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

    protected function resolveResourceRelationships(Request $request): array
    {
        if (! $this->resource instanceof Model) {
            return [];
        }

        if (is_null($this->cachedLoadedRelationships)) {
            $this->cachedLoadedRelationships = new WeakMap;
        }

        return [
            'data' => (new Collection($this->resource->getRelations()))
                ->mapWithKeys(function ($relations, $key) {
                    if ($relations instanceof Collection) {
                        $key = static::getResourceTypeFromEloquent($relations->first());

                        $relations->each(function ($relation) use ($key) {
                            $this->cachedLoadedRelationships[$relation] = [$key, $relation->getKey()];
                        });

                        return [$key => $relations->map(function ($relation) use ($key) {
                            return tap([$key, static::getResourceIdFromEloquent($relation)], function ($uniqueKey) use ($relation) {
                                $this->cachedLoadedRelationships[$relation] = $uniqueKey;
                            });
                        })];
                    }

                    return tap(
                        [static::getResourceTypeFromEloquent($relation), static::getResourceIdFromEloquent($relation)],
                        function ($uniqueKey) use ($relations) {
                            $this->cachedLoadedRelationships[$relations] = $uniqueKey;
                        }
                    );
                }),
        ];
    }

    protected function resolveResourceIncluded(Request $request): array
    {
        $relations = [];

        foreach ($this->cachedLoadedRelationships as $relation => $uniqueKey) {
            $relations[] = [
                'id' => $uniqueKey[1],
                'type' => $uniqueKey[0],
                'attributes' => rescue(fn () => $relation->toResource()->toArray($request), $relation->toArray(), false),
            ];
        }

        return $relations;
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
