<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Contracts\Database\Eloquent\Hydratable;
use Illuminate\Contracts\Database\Eloquent\Hydrator as HydratorInterface;

class Hydrator implements HydratorInterface
{
    /**
     * Fill a new Eloquent model instance with raw attributes returned from the query builder.
     *
     * @param Model $model
     * @param array $attributes
     * @param array $options
     * @return Hydratable|Model
     */
    public function hydrate(Model $model, array $attributes = [], array $options = [])
    {
        $instance = $model->newInstance([], true);

        $instance->setRawAttributes($attributes, true);

        $instance->setConnection($options['connection'] ?? $model->getConnectionName());

        $instance->fireModelEvent('retrieved', false);

        return $instance;
    }

    /**
     * Fill the pivot with raw attributes.
     *
     * @param Hydratable|Model $parent
     * @param $attributes
     * @param $table
     * @param $exists
     * @param null $using
     * @return \Illuminate\Contracts\Database\Eloquent\Hydratable|Pivot
     */
    public function hydratePivot(Model $parent, $attributes, $table, $exists, $using = null)
    {
        return method_exists($using, 'fromRawAttributes')
            ? $using::fromRawAttributes($parent, $attributes, $table, $exists)
            : Pivot::fromAttributes($parent, $attributes, $table, $exists);
    }
}
