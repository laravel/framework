<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\Attributes\WithConfig;

use function Orchestra\Testbench\artisan;

#[WithConfig('database.connections.second', ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => false])]
class EloquentTransactionWithAfterCommitUsingRefreshDatabaseOnMultipleConnectionsTest extends EloquentTransactionWithAfterCommitUsingRefreshDatabaseTest
{
    /** {@inheritDoc} */
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->markTestSkippedWhen(
                $this->usesSqliteInMemoryDatabaseConnection(), 'Skipped when default database is configured to use In-Memory SQLite Database'
            );
        });

        parent::setUp();
    }

    /** {@inheritDoc} */
    protected function connectionsToTransact()
    {
        return [null, 'second'];
    }

    /** {@inheritDoc} */
    protected function afterRefreshingDatabase()
    {
        artisan($this, 'migrate', ['--database' => 'second']);
    }
}
