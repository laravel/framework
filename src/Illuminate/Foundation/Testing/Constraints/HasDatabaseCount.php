<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;

class HasDatabaseCount extends Constraint
{
    /**
     * The table to test against.
     *
     * @var string
     */
    protected $table;

    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The data that will be used to narrow the search in the database table.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new constraint instance.
     *
     * @param  string  $table
     * @param  \Illuminate\Database\Connection  $database
     * @param  array  $data
     * @return void
     */
    public function __construct($table, Connection $database, array $data)
    {
        parent::__construct();

        $this->table = $table;

        $this->data = $data;

        $this->database = $database;
    }

    /**
     * Check if the data is found in the given table.
     *
     * @param  int  $count
     * @return bool
     */
    public function matches($count)
    {
        return $this->database->table($this->table)->where($this->data)->count() == $count;
    }

    /**
     * Get the description of the failure.
     *
     * @param  int  $count
     * @return string
     */
    public function failureDescription($count)
    {
        return sprintf(
            'count of %d rows in table [%s] matches the attributes %s',
            $count, $this->table, $this->toString(JSON_PRETTY_PRINT)
        );
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString($options = 0)
    {
        return json_encode($this->data, $options);
    }
}
