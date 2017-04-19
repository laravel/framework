<?php

namespace Illuminate\Foundation\Testing\Concerns;

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
    protected function seeInDatabase($table, array $data, $connection = null)
    {
        $count = $this->getCountForTableAndAttributes($table, $data, $connection);

        $this->assertGreaterThan(0, $count, sprintf(
            'Unable to find row in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

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
    protected function missingFromDatabase($table, array $data, $connection = null)
    {
        return $this->notSeeInDatabase($table, $data, $connection);
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function dontSeeInDatabase($table, array $data, $connection = null)
    {
        return $this->notSeeInDatabase($table, $data, $connection);
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function notSeeInDatabase($table, array $data, $connection = null)
    {
        $count = $this->getCountForTableAndAttributes($table, $data, $connection);

        $this->assertEquals(0, $count, sprintf(
            'Found unexpected records in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Assert that a given count matches the amount of rows in the database for a given where condition.
     *
     * @param  string  $table
     * @param  int  $expectedCount
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function countInDatabase($table, $expectedCount, array $data = [], $connection = null)
    {
        $count = $this->getCountForTableAndAttributes($table, $data, $connection);

        $this->assertCount($expectedCount, $count, sprintf(
            'Expected count does not match the amount of rows in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Assert that a given count is greater than the amount of rows in the database for a given where condition.
     *
     * @param  string  $table
     * @param  int  $expectedCount
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function greaterCountInDatabase($table, $expectedCount, array $data = [], $connection = null)
    {
        $count = $this->getCountForTableAndAttributes($table, $data, $connection);

        $this->assertGreaterThan($expectedCount, $count, sprintf(
            'Expected count is lower or equal than the amount of rows in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Assert that a given count is greater or equal than the amount of rows in the database for a given where condition.
     *
     * @param  string  $table
     * @param  int  $expectedCount
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function greaterOrEqualCountInDatabase($table, $expectedCount, array $data = [], $connection = null)
    {
        $count = $this->getCountForTableAndAttributes($table, $data, $connection);

        $this->assertGreaterThanOrEqual($expectedCount, $count, sprintf(
            'Expected count is lower than the amount of rows in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Assert that a given count is lower than the amount of rows in the database for a given where condition.
     *
     * @param  string  $table
     * @param  int  $expectedCount
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function lowerCountInDatabase($table, $expectedCount, array $data = [], $connection = null)
    {
        $count = $this->getCountForTableAndAttributes($table, $data, $connection);

        $this->assertLessThan($expectedCount, $count, sprintf(
            'Expected count is greater or equal than the amount of rows in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Assert that a given count is lower or equal than the amount of rows in the database for a given where condition.
     *
     * @param  string  $table
     * @param  int  $expectedCount
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function lowerOrEqualCountInDatabase($table, $expectedCount, array $data = [], $connection = null)
    {
        $count = $this->getCountForTableAndAttributes($table, $data, $connection);

        $this->assertLessThanOrEqual($expectedCount, $count, sprintf(
            'Expected count is greater than the amount of rows in database table [%s] that matched attributes [%s].', $table, json_encode($data)
        ));

        return $this;
    }

    /**
     * Returns the count for a given where condition in the database.
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return int
     */
    private function getCountForTableAndAttributes($table, array $data = [], $connection = null)
    {
        $database = $this->app->make('db');

        $connection = $connection ?: $database->getDefaultConnection();

        return $database->connection($connection)->table($table)->where($data)->count();
    }

    /**
     * Seed a given database connection.
     *
     * @param  string  $class
     * @return void
     */
    public function seed($class = 'DatabaseSeeder')
    {
        $this->artisan('db:seed', ['--class' => $class]);
    }
}
