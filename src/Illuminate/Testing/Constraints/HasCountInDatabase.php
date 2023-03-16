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
     * @param  int  $expectedCount
     * @return void
     */
    public function __construct(Connection $database, array $data, int $expectedCount)
    {
        $this->data = $data;

        $this->database = $database;

        $this->expectedCount = $expectedCount;
    }

    /**
     * Check if the data is found in the given table.
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
            "table [%s] matches expected entries count of %s. Entries found: %s.\n\n%s",
            $table, $this->expectedCount, $this->actualCount, $this->getAdditionalInfo($table)
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
        $query = $this->database->table($table);

        $similarResults = $query->where(
            array_key_first($this->data),
            $this->data[array_key_first($this->data)]
        )->select(array_keys($this->data))->limit($this->show)->get();

        if ($similarResults->isNotEmpty()) {
            $description = 'Found similar results: '.json_encode($similarResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $query = $this->database->table($table);

            $results = $query->select(array_keys($this->data))->limit($this->show)->get();

            if ($results->isEmpty()) {
                return 'The table is empty';
            }

            $description = 'Found: '.json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if ($query->count() > $this->show) {
            $description .= sprintf(' and %s others', $query->count() - $this->show);
        }

        return $description;
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
