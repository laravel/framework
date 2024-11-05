<?php

namespace Illuminate\Tests\Integration\Database;

use function Orchestra\Testbench\artisan;

class EloquentTransactionWithAfterCommitUsingRefreshDatabaseOnMultipleConnectionsTest extends EloquentTransactionWithAfterCommitUsingRefreshDatabaseTest
{
    /** {@inheritDoc} */
    protected function connectionsToTransact()
    {
        return [null, 'second'];
    }

    /** {@inheritDoc} */
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app->make('config')->set([
            'database.connections.second' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => false,
            ],
        ]);
    }

    /** {@inheritDoc} */
    protected function afterRefreshingDatabase()
    {
        artisan($this, 'migrate', ['--database' => 'second']);
    }
}
