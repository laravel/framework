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
    }

    protected function getEnvironmentSetUp($app)
    {
        $connection = $app->make('config')->get('database.default');

        $db = $app['config']->get("database.connections.{$connection}");

        if ($db['driver'] === 'sqlite' && $db['database'] == ':memory:') {
            $this->markTestSkipped('Test cannot be used with in-memory SQLite connection.');
        }

        $this->driver = $db['driver'];
    }
}
