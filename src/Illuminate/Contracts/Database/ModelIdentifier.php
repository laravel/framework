<?php

namespace Illuminate\Contracts\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ModelIdentifier
{
    /**
     * The class name of the model.
     *
     * @var string|null
     */
    public $class;

    /**
     * The unique identifier of the model.
     *
     * This may be either a single ID or an array of IDs.
     *
     * @var mixed
     */
    public $id;

    /**
     * The relationships loaded on the model.
     *
     * @var array
     */
    public $relations;

    /**
     * The connection name of the model.
     *
     * @var string|null
     */
    public $connection;

    /**
     * Create a new model identifier.
     *
     * @param  string|null  $class
     * @param  mixed  $id
     * @param  array  $relations
     * @param  mixed  $connection
     * @return void
     */
    public function __construct($class, $id, array $relations, $connection)
    {
        $this->id = $id;
        $this->class = $class;
        $this->relations = $relations;
        $this->connection = $connection;
    }

    /**
     * @return class-string<Model>|null
     */
    public function getClass(): ?string
    {
        return Relation::getMorphedModel($this->class) ?? $this->class;
    }
}
