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
        ParallelTesting::setUpProcess([$this, 'recreateDatabasesWhenRequested']);

        ParallelTesting::setUpTestCase([$this, 'updateDatabasesWhenNeeded']);
    }

    /**
     * Recreates databases if requested.
     *
     * @return void
     */
    public function recreateDatabasesWhenRequested()
    {
        $this->whenNotUsingInMemoryDatabase(function ($database) {
            if (ParallelTesting::option('recreate_databases')) {
                Schema::dropDatabaseIfExists(
                    $this->testDatabase($database)
                );
            }
        });
    }

    /**
     * Update database configuration and run migrations if needed.
     *
     * @param  Testing\TestCase  $testCase
     *
     * @return void
     */
    public function updateDatabasesWhenNeeded(Testing\TestCase $testCase)
    {
        $uses = array_flip(class_uses_recursive(get_class($testCase)));

        $databaseTraits = [
            Testing\DatabaseMigrations::class,
            Testing\DatabaseTransactions::class,
            Testing\RefreshDatabase::class,
        ];

        if (Arr::hasAny($uses, $databaseTraits)) {
            $connections = $this->connectionsToUpdate($testCase);

            $this->whenNotUsingInMemoryDatabase(function ($database) use ($connections, $uses) {
                [$testDatabase, $created] = $this->ensureTestDatabaseExists($database, $connections);

                $this->switchToDatabase($connections, $testDatabase);

                if (isset($uses[Testing\DatabaseTransactions::class])) {
                    $this->ensureSchemaIsUpToDate();
                }

                if ($created) {
                    ParallelTesting::callSetUpTestDatabaseCallbacks($testDatabase);
                }
            });
        }
    }

    /**
     * Ensure a test database exists and returns its name.
     *
     * @param  string  $database
     * @param  array  $connections
     *
     * @return array
     */
    protected function ensureTestDatabaseExists($database, array $connections)
    {
        $testDatabase = $this->testDatabase($database);

        try {
            $this->usingDatabase($testDatabase, $connections, function () {
                Schema::hasTable('dummy');
            });
        } catch (QueryException $e) {
            $this->usingDatabase($database, $connections, function () use ($testDatabase) {
                Schema::dropDatabaseIfExists($testDatabase);
                Schema::createDatabase($testDatabase);
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
     * @param  array  $connections
     * @param  callable  $callable
     *
     * @return void
     */
    protected function usingDatabase($database, array $connections, $callable)
    {
        $original = DB::getConfig('database');

        try {
            $this->switchToDatabase($connections, $database);
            $callable();
        } finally {
            $this->switchToDatabase($connections, $original);
        }
    }

    /**
     * Apply the given callback when tests are not using in memory database.
     *
     * @param  callable  $callback
     *
     * @return void
     */
    protected function whenNotUsingInMemoryDatabase($callback)
    {
        $database = DB::getConfig('database');

        if ($database !== ':memory:') {
            $callback($database);
        }
    }

    /**
     * Switch to the given database.
     *
     * @param  array  $connections
     * @param  string  $database
     *
     * @return void
     */
    protected function switchToDatabase(array $connections, $database)
    {
        foreach ($connections as $connection) {
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
     * The database connections that should be updated.
     *
     * @param  mixed  $testCase
     *
     * @return array
     */
    protected function connectionsToUpdate($testCase)
    {
        return property_exists($testCase, 'connectionsToUpdate')
            ? $testCase->connectionsToUpdate : [config('database.default')];
    }
}
