<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class DatabaseConnectionsTest extends DatabaseTestCase
{
    public function testBuildDatabaseConnection()
    {
        /** @var \Illuminate\Database\DatabaseManager $manager */
        $manager = $this->app->make(DatabaseManager::class);

        $connection = $manager->build([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->assertInstanceOf(SQLiteConnection::class, $connection);
    }

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

    public function testTablePrefix()
    {
        DB::setTablePrefix('prefix_');
        $this->assertSame('prefix_', DB::getTablePrefix());

        DB::withoutTablePrefix(function ($connection) {
            $this->assertSame('', $connection->getTablePrefix());
        });

        $this->assertSame('prefix_', DB::getTablePrefix());

        DB::setTablePrefix('');
        $this->assertSame('', DB::getTablePrefix());
    }

    public function testDynamicConnectionDoesntFailOnReconnect()
    {
        $connection = DB::build([
            'name' => 'projects',
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->expectNotToPerformAssertions();

        try {
            $connection->reconnect();
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'Database connection [projects] not configured.') {
                $this->fail('Dynamic connection should not throw an exception on reconnect.');
            }
        }
    }

    public function testDynamicConnectionWithNoNameDoesntFailOnReconnect()
    {
        $connection = DB::build([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->expectNotToPerformAssertions();

        try {
            $connection->reconnect();
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'Database connection [projects] not configured.') {
                $this->fail('Dynamic connection should not throw an exception on reconnect.');
            }
        }
    }

    #[DataProvider('readWriteExpectations')]
    public function testReadWriteTypeIsProvidedInQueryExecutedEventAndQueryLog(string $connectionName, array $expectedTypes, ?string $loggedType)
    {
        $readPath = __DIR__.'/read.sqlite';
        $writePath = __DIR__.'/write.sqlite';
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'read' => [
                'database' => $readPath,
            ],
            'write' => [
                'database' => $writePath,
            ],
        ]);
        $events = collect();
        DB::listen($events->push(...));

        try {
            touch($readPath);
            touch($writePath);

            $connection = DB::connection($connectionName);
            $connection->enableQueryLog();

            $connection->statement('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);

            $connection->select('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);

            $connection->statement('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);

            $connection->select('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);

            $this->assertEmpty($events);
            $this->assertSame([
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'write'],
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'read'],
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'write'],
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'read'],
            ], Arr::select($connection->getQueryLog(), [
                'query', 'readWriteType',
            ]));
        } finally {
            @unlink($readPath);
            @unlink($writePath);
        }
    }

    public static function readWriteExpectations(): iterable
    {
        yield 'sqlite' => ['sqlite', ['write', 'read', 'write', 'read'], null];
        yield 'sqlite::read' => ['sqlite::read', ['read', 'read', 'read', 'read'], 'read'];
        yield 'sqlite::write' => ['sqlite::write', ['write', 'write', 'write', 'write'], 'write'];
    }

    public function testConnectionsWithoutReadWriteConfigurationAlwaysShowAsWrite()
    {
        $writePath = __DIR__.'/write.sqlite';
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $writePath,
        ]);
        $events = collect();
        DB::listen($events->push(...));

        try {
            touch($writePath);

            $connection = DB::connection('sqlite');

            $connection->statement('select 1');
            $this->assertSame('write', $events->shift()->readWriteType);

            $connection->select('select 1');
            $this->assertSame('write', $events->shift()->readWriteType);

            $connection->statement('select 1');
            $this->assertSame('write', $events->shift()->readWriteType);

            $connection->select('select 1');
            $this->assertSame('write', $events->shift()->readWriteType);
        } finally {
            @unlink($writePath);
        }
    }

    public function testQueryExceptionsProviderReadWriteType()
    {
        $readPath = __DIR__.'/read.sqlite';
        $writePath = __DIR__.'/write.sqlite';
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'read' => [
                'database' => $readPath,
            ],
            'write' => [
                'database' => $writePath,
            ],
        ]);

        try {
            touch($readPath);
            touch($writePath);

            try {
                DB::connection('sqlite::write')->statement('xxxx');
                $this->fail();
            } catch (QueryException $exception) {
                $this->assertSame('write', $exception->readWriteType);
            }

            try {
                DB::connection('sqlite::read')->statement('xxxx');
                $this->fail();
            } catch (QueryException $exception) {
                $this->assertSame('read', $exception->readWriteType);
            }

            try {
                DB::connection('sqlite')->select('xxxx', useReadPdo: true);
                $this->fail();
            } catch (QueryException $exception) {
                $this->assertSame('read', $exception->readWriteType);
            }

            try {
                DB::connection('sqlite')->select('xxxx', useReadPdo: false);
                $this->fail();
            } catch (QueryException $exception) {
                $this->assertSame('write', $exception->readWriteType);
            }
        } finally {
            @unlink($writePath);
            @unlink($readPath);
        }
    }

    #[DataProvider('readWriteExpectations')]
    public function testQueryInEventListenerCannotInterfereWithReadWriteType(string $connectionName, array $expectedTypes, ?string $loggedType)
    {
        $readPath = __DIR__.'/read.sqlite';
        $writePath = __DIR__.'/write.sqlite';
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'read' => [
                'database' => $readPath,
            ],
            'write' => [
                'database' => $writePath,
            ],
        ]);
        $events = collect();
        DB::listen($events->push(...));

        try {
            touch($readPath);
            touch($writePath);

            $connection = DB::connection($connectionName);
            $connection->enableQueryLog();

            $connection->listen(function ($query) use ($connection) {
                if ($query->sql === 'select 1') {
                    $connection->select('select 2');
                }
            });

            $connection->statement('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);
            $this->assertSame($loggedType ?? 'read', $events->shift()->readWriteType);

            $connection->select('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);
            $this->assertSame($loggedType ?? 'read', $events->shift()->readWriteType);

            $connection->statement('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);
            $this->assertSame($loggedType ?? 'read', $events->shift()->readWriteType);

            $connection->select('select 1');
            $this->assertSame(array_shift($expectedTypes), $events->shift()->readWriteType);
            $this->assertSame($loggedType ?? 'read', $events->shift()->readWriteType);

            $this->assertSame([
                ['query' => 'select 2', 'readWriteType' => $loggedType ?? 'read'],
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'write'],
                ['query' => 'select 2', 'readWriteType' => $loggedType ?? 'read'],
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'read'],
                ['query' => 'select 2', 'readWriteType' => $loggedType ?? 'read'],
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'write'],
                ['query' => 'select 2', 'readWriteType' => $loggedType ?? 'read'],
                ['query' => 'select 1', 'readWriteType' => $loggedType ?? 'read'],
            ], Arr::select($connection->getQueryLog(), [
                'query', 'readWriteType',
            ]));
        } finally {
            @unlink($readPath);
            @unlink($writePath);
        }
    }
}
