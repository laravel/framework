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
     * The connection name of the model.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The unique identifier of the model.
     *
     * This may be either a single ID or an array of IDs.
     *
     * @var mixed
     */
    public $id;

    /**
     * Create a new model identifier.
     *
     * @param  string  $class
     * @param  mixed  $connection
     * @param  mixed  $id
     * @return void
     */
    public function __construct($class, $connection, $id)
    {
        $this->id = $id;
        $this->connection = $connection;
        $this->class = $class;
    }
}
