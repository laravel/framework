<?php

namespace Illuminate\Testing\Concerns;

use Illuminate\Foundation\Testing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Schema;

trait TemporaryDatabases
{
    /**
     * Boot temporary databases service.
     *
     * @return void
     */
    protected function bootTemporaryDatabases()
    {
        ParallelTesting::setUpProcess(function () {
            $this->whenNotUsingInMemoryDatabase(function ($database) {
                [$name, $path] = $this->temporaryDatabase($database);

                File::ensureDirectoryExists(dirname($path));
                File::delete($path);
            });
        });

        ParallelTesting::setUpTestCase(function ($testCase) {
            $uses = array_flip(class_uses_recursive(get_class($testCase)));

            if (Arr::hasAny($uses, [
                Testing\DatabaseMigrations::class,
                Testing\DatabaseTransactions::class,
                Testing\RefreshDatabase::class,
            ])) {
                $this->useTemporaryDatabase();
            }
        });

        ParallelTesting::tearDownProcess(function () {
            $this->whenNotUsingInMemoryDatabase(function ($database) {
                [$name, $path] = $this->temporaryDatabase($database);

                if (File::exists($path)) {
                    Schema::dropDatabaseIfExists($name);
                    File::delete($path);
                }
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
            $name = $this->ensureTemporaryDatabaseExists($database);

            DB::purge();

            $default = config('database.default');

            config()->set(
                "database.connections.{$default}.database",
                $name,
            );
        });
    }

    /**
     * Ensure a temporary database exists, and returns it's name.
     *
     * @param  string  $database
     *
     * @return string
     */
    protected function ensureTemporaryDatabaseExists($database)
    {
        [$name, $path] = $this->temporaryDatabase($database);

        if (! File::exists($path)) {
            Schema::dropDatabaseIfExists($name);
            File::put($path, '');
            Schema::createDatabase($name);

            $this->useTemporaryDatabase();

            Artisan::call('migrate:fresh');
        }

        return $name;
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
     * Returns the temporary database name and path.
     *
     * @return array
     */
    protected function temporaryDatabase($database)
    {
        $token = ParallelTesting::token();

        $name = "{$database}_test_{$token}";
        $path = storage_path('framework/testing/temporary-databases/'.$name);

        return [$name, $path];
    }
}
