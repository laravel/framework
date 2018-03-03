<?php namespace Illuminate\Database\Eloquent;

use Illuminate\Contracts\Database\Eloquent\Hydratable;
use \Illuminate\Contracts\Database\Eloquent\Hydrator as HydratorInterface;

class Hydrator implements HydratorInterface
{

    /**
     * The original model instance.
     *
     * @var Model
     */
    protected $model;

    /**
     * A custom database connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * Hydrator constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Fill a new Eloquent model instance with raw attributes returned from the query builder.
     *
     * @param array $attributes
     * @return Hydratable
     */
    public function hydrate(array $attributes = []) : Hydratable
    {
        $model = $this->model->newInstance([], true);

        $model->setRawAttributes($attributes, true);

        $model->setConnection($this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
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
    public function on(string $connection = null) : Hydrator
    {
        $this->connection = $connection;
        return $this;
    }

}