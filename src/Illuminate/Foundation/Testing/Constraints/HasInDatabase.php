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
    protected $show = 3;

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
     * The exact number of rows expected in the database table.
     *
     * @var integer|null
     */
    protected $number;

    /**
     * Create a new constraint instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  array  $data
     * @param  integer  $number
     * @return void
     */
    public function __construct(Connection $database, array $data, $number = null)
    {
        $this->database = $database;

        $this->data = $data;

        $this->number = $number;
    }

    /**
     * Check if the data is found in the given table.
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table)
    {
        $found = $this->database->table($table)->where($this->data)->count();

        if ($this->number) {
            return $found === $this->number;
        }

        return $found > 0;
    }

    /**
     * Get the description of the failure.
     *
     * @param  string  $table
     * @return string
     */
    public function failureDescription($table)
    {
        return sprintf(
            $this->baseFailureDescription(), $table,
            $this->toString(JSON_PRETTY_PRINT), $this->getAdditionalInfo($table)
        );
    }

    /**
     * Get the base failure description (will vary depending on the number of expected rows).
     *
     * @return string
     */
    protected function baseFailureDescription()
    {
        if ($this->number === null) {
            return "a row in the table [%s] matches the attributes %s.\n\n%s";
        }

        if ($this->number === 1) {
            return "exactly one row in the table [%s] matches the attributes %s.\n\n%s";
        }

        return "{$this->number} rows in the table [%s] match the attributes %s.\n\n%s";
    }

    /**
     * Get additional info about the records found in the database table.
     *
     * @param  string  $table
     * @return string
     */
    protected function getAdditionalInfo($table)
    {
        $results = $this->database->table($table)->get();

        if ($results->isEmpty()) {
            return 'The table is empty';
        }

        $description = 'Found: '.json_encode($results->take($this->show), JSON_PRETTY_PRINT);

        if ($results->count() > $this->show) {
            $description .= sprintf(' and %s others', $results->count() - $this->show);
        }

        return $description;
    }

    /**
     * Get a string representation of the object.
     *
     * @param  int  $options
     * @return string
     */
    public function toString($options = 0)
    {
        return json_encode($this->data, $options);
    }
}
