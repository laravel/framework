<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Contracts\Database\Eloquent\Hydratable;
use Illuminate\Contracts\Database\Eloquent\Hydrator as HydratorInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
     * Get the custom connection or fallback to the default connection.
     *
     * @return string
     */
    protected function getConnectionName()
    {
        return $this->connection ?: $this->model->getConnectionName();
    }

    /**
     * Set a custom database connection.
     *
     * @param string $connection
     * @return Hydrator
     */
    public function on(string $connection = null) : self
    {
        $this->connection = $connection;

        return $this;
    }
}
