<?php

namespace Illuminate\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

class CountInDatabase extends Constraint
{
    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

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
     * Whether to swap the assertion to check that the count does not equal the expected count
     *
     * @var bool
     */
    protected $not;

    /**
     * Create a new constraint instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  int  $expectedCount
     * @return void
     */
    public function __construct(Connection $database, int $expectedCount, bool $not = false)
    {
        $this->expectedCount = $expectedCount;

        $this->database = $database;

        $this->not = $not;
    }

    /**
     * Check if the expected and actual count are equal.
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table): bool
    {
        $this->actualCount = $this->database->table($table)->count();

        if ($this->not) {
           return $this->actualCount !== $this->expectedCount;
        }

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
        if ($this->expectedCount === 0) {
            if ($this->not) {
                return sprintf(
                    "table [%s] is not empty.\n",
                    $table
                );
            }

            return sprintf(
                "table [%s] is empty. Entries found: %s.\n",
                $table,  $this->actualCount
            );
        }

        return sprintf(
            "table [%s] matches expected entries count of %s. Entries found: %s.\n",
            $table, $this->expectedCount, $this->actualCount
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
        return (new ReflectionClass($this))->name;
    }
}
