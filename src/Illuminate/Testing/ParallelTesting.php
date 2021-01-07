<?php

namespace Illuminate\Testing;

use Illuminate\Foundation\Testing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParallelTesting
{
    /**
     * The token resolver callback.
     *
     * @var \Closure|null
     */
    protected static $tokenResolver;

    /**
     * The temporary database name, if any.
     *
     * @var string|null
     */
    protected static $temporaryDatabase;

    /**
     * Set a callback that should be used when resolving tokens.
     *
     * @param  \Closure|null  $callback
     * @return void
     */
    public static function resolveTokenUsing($resolver)
    {
        static::$tokenResolver = $resolver;
    }

    /**
     * Runs before the process gets destroyed.
     *
     * @return void
     */
    public function beforeProcessDestroyed()
    {
        $this->whenNotUsingInMemoryDatabase(function ($database) {
            Schema::dropDatabaseIfExists(
                $this->addTokenIfNeeded($database),
            );
        });
    }

    /**
     * SetUp the given test case for parallel testing, if needed.
     *
     * @param  \Illuminate\Foundation\Testing\TestCase $testCase
     *
     * @return void
     */
    public function setUpIfNeeded($testCase)
    {
        $this->whenRunningInParallel(function () use ($testCase) {
            $uses = array_flip(class_uses_recursive(get_class($testCase)));

            if (Arr::hasAny($uses, [
                Testing\DatabaseMigrations::class,
                Testing\DatabaseTransactions::class,
                Testing\RefreshDatabase::class,
            ])) {
                $this->switchToTemporaryDatabase();
            }
        });
    }

    /**
     * Adds an unique test token to the given string, if needed.
     *
     * @return string
     */
    public function addTokenIfNeeded($string)
    {
        if (! $this->inParallel()) {
            return $string;
        }

        return "{$string}_test_{$this->token()}";
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
     * Apply the callback if tests are running in parallel.
     *
     * @param  callable $callback
     * @return void
     */
    protected function whenRunningInParallel($callback)
    {
        if ($this->inParallel()) {
            $callback();
        }
    }

    /**
     * Switch to the temporary database.
     *
     * @return void
     */
    protected function switchToTemporaryDatabase()
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
            static::$temporaryDatabase = $this->addTokenIfNeeded($database);

            Schema::dropDatabaseIfExists(static::$temporaryDatabase);
            Schema::createDatabase(static::$temporaryDatabase);

            $this->switchToTemporaryDatabase();

            Artisan::call('migrate:fresh');
        }

        return static::$temporaryDatabase;
    }

    /**
     * Indicates if the current tests are been run in Parallel.
     *
     * @return bool
     */
    protected function inParallel()
    {
        return ! empty($_SERVER['LARAVEL_PARALLEL_TESTING']) && $this->token();
    }

    /**
     * Gets an unique test token.
     *
     * @return int|false
     */
    protected function token()
    {
        return static::$tokenResolver
            ? call_user_func(static::$tokenResolver)
            : ($_SERVER['TEST_TOKEN'] ?? false);
    }
}
