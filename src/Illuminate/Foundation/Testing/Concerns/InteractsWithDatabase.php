<?php

namespace Illuminate\Foundation\Testing\Concerns;

use PHPUnit_Framework_Constraint_Not as ReverseConstraint;
use Illuminate\Foundation\Testing\Constraints\HasInDatabase;
use Illuminate\Foundation\Testing\Constraints\SoftDeletedInDatabase;

trait InteractsWithDatabase
{
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
        return $this->assertDatabaseHasMany($table, null, $data, $connection);
    }

    /**
     * Assert that a given where condition exists only one time in the database.
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertDatabaseHasOne($table, array $data, $connection = null)
    {
        return $this->assertDatabaseHasMany($table, 1, $data, $connection);
    }

    /**
     * Assert that a given where condition exists only a certain amount of times in the database.
     *
     * @param  string  $table
     * @param  int|null  $number
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertDatabaseHasMany($table, $number, array $data, $connection = null)
    {
        $this->assertThat(
            $table, new HasInDatabase($this->getConnection($connection), $data, $number)
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
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertSoftDeleted($table, array $data, $connection = null)
    {
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
     * @param  string  $class
     * @return $this
     */
    public function seed($class = 'DatabaseSeeder')
    {
        $this->artisan('db:seed', ['--class' => $class]);

        return $this;
    }
}
