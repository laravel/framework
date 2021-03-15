<?php

namespace Illuminate\Testing\Constraints;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use PHPUnit\Framework\Constraint\Constraint;

class HasCountInDatabase extends Constraint
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
     * The expected table entries count that will be checked against the actual count.
     *
     * @var int
     */
    protected $expectedCount;

    /**
     * The actual table entries count that will be checked against the expected count.
     *
     * @var int
     */
    protected $actualCount;

    /**
     * Create a new constraint instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  array  $data
     * @param  int  $expectedCount
     * @return void
     */
    public function __construct(Connection $database, array $data, int $expectedCount)
    {
        $this->data = $data;

        $this->expectedCount = $expectedCount;

        $this->database = $database;
    }

    /**
     * Check if the expected and actual count are equal.
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table): bool
    {
        $this->actualCount = $this->database->table($table)->where($this->data)->count();

        return $this->actualCount === $this->expectedCount;
    }

    /**
     * Get the description of the failure.
     *
     * @param  string  $table
     * @return string
     */
    public function failureDescription($table): string
    {
        return sprintf(
            "table [%s] with attributes\n%s\nmatches expected entries count of %s. Matching entries found: %s.\n",
            $table, $this->toString(JSON_PRETTY_PRINT), $this->expectedCount, $this->actualCount
        );
    }

    /**
     * Get a string representation of the object.
     *
     * @param  int  $options
     * @return string
     */
    public function toString($options = 0): string
    {
        foreach ($this->data as $key => $data) {
            $output[$key] = $data instanceof Expression ? (string) $data : $data;
        }

        return json_encode($output ?? [], $options);
    }
}
