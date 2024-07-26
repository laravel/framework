<?php

namespace Illuminate\Database\Eloquent;

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
        return new static::$collectionClass($models);
    }
}
