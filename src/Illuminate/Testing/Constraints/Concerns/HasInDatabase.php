<?php

namespace Illuminate\Testing\Constraints\Concerns;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection;

trait HasInDatabase
{
    /**
     * Number of records that will be shown in the console in case of failure.
     *
     * @var int
     */
    protected readonly int $show;

    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected readonly Connection $database;

    /**
     * The data that will be used to narrow the search in the database table.
     *
     * @var array
     */
    protected readonly array $data;

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

        $this->show = 3;
    }

    /**
     * Check if the data is found in the given table.
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table): bool
    {
        return $this->database->table($table)->where($this->data)->count() > 0;
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
            "a row in the table [%s] matches the attributes %s.\n\n%s",
            $table, $this->toString(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $this->getAdditionalInfo($table)
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
            $output[$key] = $data instanceof Expression ? $data->getValue($this->database->getQueryGrammar()) : $data;
        }

        return json_encode($output ?? [], $options);
    }
}
