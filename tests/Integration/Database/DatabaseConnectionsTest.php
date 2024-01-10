<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Events\Dispatcher;
use RuntimeException;

class DatabaseConnectionsTest extends DatabaseTestCase
{
    public function testEstablishDatabaseConnection()
    {
        /** @var \Illuminate\Database\DatabaseManager $manager */
        $manager = $this->app->make(DatabaseManager::class);

        $connection = $manager->connectUsing('my-phpunit-connection', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $connection->statement('CREATE TABLE test_1 (id INTEGER PRIMARY KEY)');

        $connection->statement('INSERT INTO test_1 (id) VALUES (1)');

        $result = $connection->selectOne('SELECT COUNT(*) as total FROM test_1');

        self::assertSame(1, $result->total);
    }

    public function testThrowExceptionIfConnectionAlreadyExists()
    {
        /** @var \Illuminate\Database\DatabaseManager $manager */
        $manager = $this->app->make(DatabaseManager::class);

        $manager->connectUsing('my-phpunit-connection', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->expectException(RuntimeException::class);

        $manager->connectUsing('my-phpunit-connection', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    public function testOverrideExistingConnection()
    {
        /** @var \Illuminate\Database\DatabaseManager $manager */
        $manager = $this->app->make(DatabaseManager::class);

        $connection = $manager->connectUsing('my-phpunit-connection', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $connection->statement('CREATE TABLE test_1 (id INTEGER PRIMARY KEY)');

        $resultBeforeOverride = $connection->select("SELECT name FROM sqlite_master WHERE type='table';");

        $connection = $manager->connectUsing('my-phpunit-connection', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], force: true);

        // After purging a connection of a :memory: SQLite database
        // anything that was created before the override will no
        // longer be available. It's a new and fresh database
        $resultAfterOverride = $connection->select("SELECT name FROM sqlite_master WHERE type='table';");

        self::assertSame('test_1', $resultBeforeOverride[0]->name);

        self::assertEmpty($resultAfterOverride);
    }

    public function testEstablishingAConnectionWillDispatchAnEvent()
    {
        /** @var \Illuminate\Events\Dispatcher $dispatcher */
        $dispatcher = $this->app->make(Dispatcher::class);

        $event = null;

        $dispatcher->listen(ConnectionEstablished::class, function (ConnectionEstablished $e) use (&$event) {
            $event = $e;
        });

        /** @var \Illuminate\Database\DatabaseManager $manager */
        $manager = $this->app->make(DatabaseManager::class);

        $manager->connectUsing('my-phpunit-connection', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        self::assertInstanceOf(
            ConnectionEstablished::class,
            $event,
            'Expected the ConnectionEstablished event to be dispatched when establishing a connection.'
        );

        self::assertSame('my-phpunit-connection', $event->connectionName);
    }
}
