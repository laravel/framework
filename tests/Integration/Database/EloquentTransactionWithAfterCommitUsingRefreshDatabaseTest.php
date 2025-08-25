<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class EloquentTransactionWithAfterCommitUsingRefreshDatabaseTest extends TestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use RefreshDatabase;

    /**
     * The current database driver.
     *
     * @return string
     */
    protected $driver;

    /** {@inheritDoc} */
    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            foreach (array_keys($this->app['db']->getConnections()) as $name) {
                $this->app['db']->purge($name);
            }
        });

        parent::setUp();
    }

    /** {@inheritDoc} */
    protected function defineEnvironment($app)
    {
        $connection = $app['config']->get('database.default');

        $this->driver = $app['config']->get("database.connections.$connection.driver");
    }
}
