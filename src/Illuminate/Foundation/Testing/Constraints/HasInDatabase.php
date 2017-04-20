<?php

namespace Illuminate\Foundation\Testing\Constraints;

use PHPUnit_Framework_Constraint;
use Illuminate\Database\Connection;

class HasInDatabase extends PHPUnit_Framework_Constraint
{
    /**
     * Number of records that will be shown in the console in case of failure.
     *
     * @var int
     */
    protected $take = 5;

    /**
     * Database connection.
     *
     * @var \Illuminate\Database\Collection
     */
    protected $database;

    /**
     * Data that will be used to narrow the search in the database table.
     *
     * @var array
     */
    protected $data;

    /**
     * Name of the queried database table.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new constraint instance.
     *
     * @param  array  $data
     * @param  \Illuminate\Database\Collection  $database
     */
    public function __construct(array $data, Connection $database)
    {
        $this->data = $data;

        $this->database = $database;
    }

    /**
     * Check if the data is found in the given table.
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table)
    {
        $this->table = $table;

        return $this->database->table($table)->where($this->data)->count() > 0;
    }

    /**
     * Get the database results.
     *
     * @return \Illuminate\Database\Collection
     */
    public function getResults()
    {
        return $this->database->table($this->table)->get();
    }

    /**
     * Get the description of the failure.
     *
     * @param  string  $table
     * @return string
     */
    public function failureDescription($table)
    {
        $results = $this->getResults();

        $description = sprintf(
            "a row in the table \"%s\" matches the attributes %s.\n\nFound instead: %s",
            $table, $this->toString(), json_encode($results->take(5))
        );

        if ($this->take > 5) {
            $description .= sprintf(' and %s others.', $this->count - $results->count());
        }

        return $description;
    }

    /**
     * Get a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return json_encode($this->data);
    }
}
