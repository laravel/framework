<?php

namespace Illuminate\Contracts\Database;

class ModelIdentifier
{
    /**
     * The class name of the model.
     *
     * @var string
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
     * The connection name of the model.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The changes applied to the model.
     *
     * @var array
     */
    public $changes = [];

    /**
     * Create a new model identifier.
     *
     * @param  string  $class
     * @param  mixed  $id
     * @param  mixed  $connection
     * @return void
     */
    public function __construct($class, $id, $connection)
    {
        $this->id = $id;
        $this->class = $class;
        $this->connection = $connection;
    }

    /**
     * Set the changes applied to the mode;.
     *
     * @param  array  $changes
     * @return $this
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * The changes applied to the model.
     *
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }
}
