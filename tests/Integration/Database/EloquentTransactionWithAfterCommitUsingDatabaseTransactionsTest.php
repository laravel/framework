<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Orchestra\Testbench\TestCase;

class EloquentTransactionWithAfterCommitUsingDatabaseTransactionsTest extends TestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use DatabaseTransactions;

    /**
     * The current database driver.
     *
     * @return string
     */
    protected $driver;

    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            foreach (array_keys($this->app['db']->getConnections()) as $name) {
                $this->app['db']->purge($name);
            }
        });

        parent::setUp();

        if ($this->usesSqliteInMemoryDatabaseConnection()) {
            $this->markTestSkipped('Test cannot be used with in-memory SQLite connection.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $connection = $app->make('config')->get('database.default');

        $this->driver = $app['config']->get("database.connections.$connection.driver");
    }
}
