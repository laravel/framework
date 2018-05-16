<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;

class CountInDatabase extends Constraint
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
     * The expected count the search will produce in database.
     *
     * @var int
     */
    protected $count;

    /**
     * Create a new constraint instance.
     *
     * @param  \Illuminate\Database\Connection $database
     * @param  array $data
     * @param  int $count
     * @return void
     */
    public function __construct(Connection $database, array $data, int $count)
    {
        $this->data = $data;

        $this->database = $database;

        $this->count = $count;
    }

    /**
     * Check if the data found in the given table counts as expected.
     *
     * @param  string $table
     * @return bool
     */
    public function matches($table): bool
    {
        return $this->database->table($table)->where($this->data)->count() == $this->count;
    }

    /**
     * Get the description of the failure.
     *
     * @param  string $table
     * @return string
     */
    public function failureDescription($table): string
    {
        return sprintf("a row in the table [%s] matches the attributes %s.\n\n%s", $table, $this->toString(JSON_PRETTY_PRINT), $this->getAdditionalInfo($table));
    }

    /**
     * Get additional info about the records found in the database table.
     *
     * @param  string $table
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
     * @param  int $options
     * @return string
     */
    public function toString($options = 0): string
    {
        return json_encode($this->data, $options);
    }
}
