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
     * Create a new constraint instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  array  $data
     * @return void
     */
    public function __construct(Connection $database, array $data)
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
        return $this->database->table($table)->where($this->data)->count() > 0;
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
            "a row in the table [%s] matches the attributes %s.\n\n%s",
            $table, $this->toString(), $this->getAdditionalInfo($table)
        );
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
     * @return string
     */
    public function toString()
    {
        return json_encode($this->data);
    }
}
