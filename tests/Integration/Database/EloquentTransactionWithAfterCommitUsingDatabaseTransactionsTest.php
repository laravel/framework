<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class EloquentTransactionWithAfterCommitUsingDatabaseTransactionsTest extends DatabaseTestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use DatabaseTransactions;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $connection = $app->make('config')->get('database.default');

        $db = $app['config']->get("database.connections.{$connection}");

        if ($db['driver'] === 'sqlite' && $db['database'] == ':memory:') {
            $this->markTestSkipped('Test cannot be used with in-memory SQLite connection.');
        }
    }
}
