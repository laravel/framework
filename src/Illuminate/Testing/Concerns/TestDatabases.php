<?php

namespace Illuminate\Testing\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Schema;

trait TestDatabases
{
    /**
     * Indicates if the test database schema is up to date.
     *
     * @var bool
     */
    protected static $schemaIsUpToDate = false;

    /**
     * Boot a test database.
     *
     * @return void
     */
    protected function bootTestDatabase()
    {
        ParallelTesting::setUpProcess(function () {
            $this->whenNotUsingInMemoryDatabase(function ($database, $connection) {
                if (ParallelTesting::option('recreate_databases')) {
                    Schema::connection($connection)->dropDatabaseIfExists(
                        $this->testDatabase($database)
                    );
                }
            }, $this->getConnections());
        });

        ParallelTesting::setUpTestCase(function ($testCase) {
            $uses = array_flip(class_uses_recursive(get_class($testCase)));

            $databaseTraits = [
                Testing\DatabaseMigrations::class,
                Testing\DatabaseTransactions::class,
                Testing\DatabaseTruncation::class,
                Testing\RefreshDatabase::class,
            ];

            if (Arr::hasAny($uses, $databaseTraits) && ! ParallelTesting::option('without_databases')) {

                $testDatabases = $this->whenNotUsingInMemoryDatabase(function ($database, $connection) {
                    [$testDatabase, $created] = $this->ensureTestDatabaseExists($database, $connection);

                    $this->switchToDatabase($testDatabase, $connection);

                    return [$testDatabase, $created];
                }, $this->getConnections());

                if ($testDatabases->count()) {
                    if (isset($uses[Testing\DatabaseTransactions::class])) {
                        $this->ensureSchemaIsUpToDate();
                    }
                }

                $testDatabases->each(function($params) {
                    [$testDatabase, $created] = $params;

                    if ($created) {
                        ParallelTesting::callSetUpTestDatabaseCallbacks($testDatabase);
                    }

                });
            }
        });

        ParallelTesting::tearDownProcess(function () {
            $this->whenNotUsingInMemoryDatabase(function ($database, $connection) {
                if (ParallelTesting::option('drop_databases')) {
                    Schema::connection($connection)->dropDatabaseIfExists(
                        $this->testDatabase($database)
                    );
                }
            }, $this->getConnections());
        });
    }

    /**
     * Ensure a test database exists and returns its name.
     *
     * @param  string  $database
     * @return array
     */
    protected function ensureTestDatabaseExists($database, $connection)
    {
        $testDatabase = $this->testDatabase($database);

        try {
            $this->usingDatabase($testDatabase, $connection, function () use ($connection) {
                Schema::connection($connection)->hasTable('dummy');
            });
        } catch (QueryException) {
            $this->usingDatabase($database, $connection, function () use ($testDatabase, $connection) {
                Schema::connection($connection)->dropDatabaseIfExists($testDatabase);
                Schema::connection($connection)->createDatabase($testDatabase);
            });

            return [$testDatabase, true];
        }

        return [$testDatabase, false];
    }

    /**
     * Ensure the current database test schema is up to date.
     *
     * @return void
     */
    protected function ensureSchemaIsUpToDate()
    {
        if (! static::$schemaIsUpToDate) {
            Artisan::call('migrate');

            static::$schemaIsUpToDate = true;
        }
    }

    /**
     * Runs the given callable using the given database.
     *
     * @param  string  $database
     * @param string $connection
     * @param  callable  $callable
     * @return void
     */
    protected function usingDatabase($database, $connection, $callable)
    {
        $original = DB::connection($connection)->getConfig('database');

        try {
            $this->switchToDatabase($database, $connection);
            $callable();
        } finally {
            $this->switchToDatabase($original, $connection);
        }
    }

    /**
     * Apply the given callback when tests are not using in memory database.
     *
     * @param  callable  $callback
     * @param array $connections
     * @return Collection
     */
    protected function whenNotUsingInMemoryDatabase($callback, $connections)
    {
        if (ParallelTesting::option('without_databases')) {
            return;
        }

        return collect($connections)
            ->map(function ($connection) use ($callback) {
                $database = DB::connection($connection)->getConfig('database');

                if ($database !== ':memory:') {
                    return $callback($database, $connection);
                }
            })
            ->filter();
    }

    /**
     * Switch to the given database.
     *
     * @param  string  $database
     * @param string $connection
     * @return void
     */
    protected function switchToDatabase($database, $connection)
    {
        DB::purge($connection);

        $url = config("database.connections.{$connection}.url");

        if ($url) {
            config()->set(
                "database.connections.{$connection}.url",
                preg_replace('/^(.*)(\/[\w-]*)(\??.*)$/', "$1/{$database}$3", $url),
            );
        } else {
            config()->set(
                "database.connections.{$connection}.database",
                $database,
            );
        }
    }

    /**
     * Returns the test database name.
     *
     * @return string
     */
    protected function testDatabase($database)
    {
        $token = ParallelTesting::token();

        return "{$database}_test_{$token}";
    }

    /**
     * Returns the database connections to be used.
     *
     * @return array
     */
    protected function getConnections(): array
    {
        return ParallelTesting::option('connections') ?: [DB::getDefaultConnection()];
    }
}
