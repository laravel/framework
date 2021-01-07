<?php

namespace Illuminate\Testing\Concerns;

use Illuminate\Foundation\Testing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Schema;

trait TemporaryDatabases
{
    /**
     * The current temporary database name, if any.
     *
     * @var string|null
     */
    protected static $temporaryDatabase;

    /**
     * Boot temporary databases service.
     *
     * @return void
     */
    protected function bootTemporaryDatabases()
    {
        ParallelTesting::setUp(function ($testCase) {
            $uses = array_flip(class_uses_recursive(get_class($testCase)));

            if (Arr::hasAny($uses, [
                Testing\DatabaseMigrations::class,
                Testing\DatabaseTransactions::class,
                Testing\RefreshDatabase::class,
            ])) {
                $this->useTemporaryDatabase();
            }
        });

        ParallelTesting::beforeProcessDestroyed(function () {
            $this->whenNotUsingInMemoryDatabase(function ($database) {
                Schema::dropDatabaseIfExists(
                    $this->temporaryDatabaseName($database)
                );
            });
        });
    }

    /**
     * Use a temporary database.
     *
     * @return void
     */
    protected function useTemporaryDatabase()
    {
        $this->whenNotUsingInMemoryDatabase(function ($database) {
            $database = $this->ensureTemporaryDatabaseExists($database);

            DB::purge();

            $default = config('database.default');

            config()->set(
                "database.connections.{$default}.database",
                $database,
            );
        });
    }

    /**
     * Ensure a temporary database exists.
     *
     * @param  string  $database
     *
     * @return string
     */
    protected function ensureTemporaryDatabaseExists($database)
    {
        if (! static::$temporaryDatabase) {
            static::$temporaryDatabase = $this->temporaryDatabaseName($database);

            Schema::dropDatabaseIfExists(static::$temporaryDatabase);
            Schema::createDatabase(static::$temporaryDatabase);

            $this->useTemporaryDatabase();

            Artisan::call('migrate:fresh');
        }

        return static::$temporaryDatabase;
    }

    /**
     * Apply the callback when tests are not using in memory database.
     *
     * @param  callable $callback
     * @return void
     */
    protected function whenNotUsingInMemoryDatabase($callback)
    {
        $database = DB::getConfig('database');

        if ($database != ':memory:') {
            $callback($database);
        }
    }

    /**
     * Returns the temporary database name.
     *
     * @return string
     */
    protected function temporaryDatabaseName($database)
    {
        $token = ParallelTesting::token();

        return "{$database}_test_{$token}";
    }
}
