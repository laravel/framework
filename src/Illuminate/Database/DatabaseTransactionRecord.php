<?php

namespace Illuminate\Database;

class DatabaseTransactionRecord
{
    /**
     * The name of the database connection.
     *
     * @var string
     */
    public $connection;

    /**
     * The transaction level.
     *
     * @var int
     */
    public $level;

    /**
     * The transaction counter.
     *
     * @var int
     */
    public $counter;

    /**
     * The callbacks that should be executed after committing.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Create a new database transaction record instance.
     *
     * @param  string  $connection
     * @param  int  $level
     * @param  int  $counter
     * @return void
     */
    public function __construct($connection, $level, $counter)
    {
        $this->connection = $connection;
        $this->level = $level;
        $this->counter = $counter;
    }

    /**
     * Register a callback to be executed after committing.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Execute all of the callbacks.
     *
     * @return void
     */
    public function executeCallbacks()
    {
        foreach ($this->callbacks as $callback) {
            $callback();
        }
    }

    /**
     * Get all of the callbacks.
     *
     * @return array
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }
}
