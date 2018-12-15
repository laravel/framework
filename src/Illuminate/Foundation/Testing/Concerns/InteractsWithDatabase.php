<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\Constraints\HasInDatabase;
use PHPUnit\Framework\Constraint\LogicalNot as ReverseConstraint;
use Illuminate\Foundation\Testing\Constraints\SoftDeletedInDatabase;

trait InteractsWithDatabase
{
    protected $databaseQueriesExecuted = null;

    /**
     * Assert that a given where condition exists in the database.
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertDatabaseHas($table, array $data, $connection = null)
    {
        $this->assertThat(
            $table, new HasInDatabase($this->getConnection($connection), $data)
        );

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertDatabaseMissing($table, array $data, $connection = null)
    {
        $constraint = new ReverseConstraint(
            new HasInDatabase($this->getConnection($connection), $data)
        );

        $this->assertThat($table, $constraint);

        return $this;
    }

    /**
     * Assert the given record has been deleted.
     *
     * @param  string|\Illuminate\Database\Eloquent\Model  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertSoftDeleted($table, array $data = [], $connection = null)
    {
        if ($table instanceof Model) {
            return $this->assertSoftDeleted($table->getTable(), [$table->getKeyName() => $table->getKey()], $table->getConnectionName());
        }

        $this->assertThat(
            $table, new SoftDeletedInDatabase($this->getConnection($connection), $data)
        );

        return $this;
    }

    /**
     * Get the database connection.
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection($connection = null)
    {
        $database = $this->app->make('db');

        $connection = $connection ?: $database->getDefaultConnection();

        return $database->connection($connection);
    }

    /**
     * Seed a given database connection.
     *
     * @param  array|string  $class
     * @return $this
     */
    public function seed($class = 'DatabaseSeeder')
    {
        foreach (Arr::wrap($class) as $class) {
            $this->artisan('db:seed', ['--class' => $class, '--no-interaction' => true]);
        }

        return $this;
    }

    /**
     * Start counting executed database queries.
     *
     * @return $this
     */
    public function startCountingDatabaseQueries()
    {
        if ($this->databaseQueriesExecuted === null) {
            $this->app->make('db')->listen(function ($query) {
                $this->databaseQueriesExecuted++;
            });
        }

        $this->databaseQueriesExecuted = 0;

        return $this;
    }

    /**
     * Assert the amount of database queries executed since we started counting.
     *
     * @param  int
     * @return $this
     */
    public function assertDatabaseQueriesExecutedCount($expectedCount)
    {
        if ($this->databaseQueriesExecuted === null) {
            $this->fail('You have to call "startCountingDatabaseQueries()" first');
        }

        $this->assertSame(
            $expectedCount,
            $this->databaseQueriesExecuted,
            "Failed to assert that the amount of executed database queries matched the expected {$expectedCount}"
        );

        return $this;
    }
}
