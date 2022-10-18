<?php


namespace Illuminate\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

class emptyDatabase extends Constraint
{
    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * Create a new constraint instance.
     *
     * @param \Illuminate\Database\Connection $database
     * @return void
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * Check if the data is found in the given table.
     *
     * @param string $table
     * @return bool
     */
    public function matches($table): bool
    {
        return $this->database->table($table)->count() === 0;
    }

    /**
     * Get the description of the failure.
     *
     * @param string $table
     * @return string
     */
    public function failureDescription($table): string
    {
        return sprintf(
            'the table [%s] is not empty.', $table
        );
    }

    /**
     * Get a string representation of the object.
     *
     * @param int $options
     * @return string
     */
    public function toString($options = 0): string
    {
        return (new ReflectionClass($this))->name;
    }
}
