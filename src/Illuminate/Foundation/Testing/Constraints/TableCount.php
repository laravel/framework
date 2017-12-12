<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;

class TableCount extends Constraint
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
     * The count that needs to be asserted for the given table
     *
     * @var int
     */
    protected $wantedCount;

    /**
     * Create a new constraint instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  int  $wantedCount
     * @return void
     */
    public function __construct(Connection $database, int $wantedCount)
    {
        $this->wantedCount = $wantedCount;

        $this->database = $database;
    }

    /**
     * Check if the number of records in $table match $this->wantedCount
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table)
    {
        return $this->database->table($table)->count() == $this->wantedCount;
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
            "table %s has a different number of records (%d) than expected (%d).\n",
            $table, $this->database->table($table)->count(), $this->wantedCount
        );
    }

    /**
     * Get a string representation of the object.
     *
     * @param  int  $options
     * @return string
     */
    public function toString($options = 0)
    {
        return json_encode($this->wantedCount, $options);
    }
}
