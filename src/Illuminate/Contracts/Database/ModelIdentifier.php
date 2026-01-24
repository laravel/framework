<?php

namespace Illuminate\Contracts\Database;

use Illuminate\Database\Eloquent\Relations\Relation;

class ModelIdentifier
{
    /**
     * The class name of the model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>|string|null
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
     * The class name of the model collection.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Collection>|null
     */
    public $collectionClass;

    /**
     * Create a new model identifier.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|null  $class
     * @param  mixed  $id
     * @param  array  $relations
     * @param  mixed  $connection
     */
    public function __construct($class, $id, array $relations, $connection)
    {
        $this->class = $class === null ? null : Relation::getMorphAlias($class);
        $this->id = $id;
        $this->relations = $relations;
        $this->connection = $connection;
    }

    /**
     * Specify the collection class that should be used when serializing / restoring collections.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Collection>  $collectionClass
     * @return $this
     */
    public function useCollectionClass(?string $collectionClass)
    {
        $this->collectionClass = $collectionClass;

        return $this;
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    public function getClass(): ?string
    {
        if ($this->class === null) {
            return null;
        }

        return Relation::getMorphedModel($this->class) ?? $this->class;
    }
}
