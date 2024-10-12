<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use ReflectionClass;

/**
 * @template TCollection of \Illuminate\Database\Eloquent\Collection
 */
trait HasCollection
{
    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array<array-key, \Illuminate\Database\Eloquent\Model>  $models
     * @return TCollection
     */
    public function newCollection(array $models = [])
    {
        $collectionClass = $this->resolveCollectionAttribute() ?? static::$collectionClass;

        return new $collectionClass($models);
    }

    /**
     * Resolve the collection class name from the CollectedBy attribute.
     *
     * @return ?class-string<TCollection>
     */
    public function resolveCollectionAttribute()
    {
        return once(function () {
            $reflectionClass = new ReflectionClass(static::class);
            $attributes = $reflectionClass->getAttributes(CollectedBy::class);

            if (! isset($attributes[0]) || ! isset($attributes[0]->getArguments()[0])) {
                return;
            }

            return $attributes[0]->getArguments()[0];
        });
    }
}
