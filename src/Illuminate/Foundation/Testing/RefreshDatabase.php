<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Testing;

trait RefreshDatabase
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        if ($this->usingInMemoryDatabase()) {
            return $this->refreshInMemoryDatabase();
        }

        Testing::whenRunningInParallel(function () {
            $this->switchToTemporaryDatabase();
        });

        $this->refreshTestDatabase();
    }

    /**
     * Determine if an in-memory database is being used.
     *
     * @return bool
     */
    protected function usingInMemoryDatabase()
    {
        $default = config('database.default');

        return config("database.connections.$default.database") === ':memory:';
    }

    /**
     * Switch to the temporary test database.
     *
     * @return void
     */
    protected function switchToTemporaryDatabase()
    {
        $default = config('database.default');

        config()->set(
            "database.connections.{$default}.database",
            RefreshDatabaseState::$temporaryDatabase,
        );
    }

    /**
     * Creates a temporary database, if needed.
     *
     * @beforeClass
     *
     * @return void
     */
    public static function setUpTemporaryDatabase()
    {
        tap(new static(), function ($testCase) {
            $testCase->refreshApplication();

            if ($testCase->usingInMemoryDatabase()) {
                return;
            }

            Testing::whenRunningInParallel(function () use ($testCase) {
                $name = $testCase->getConnection()->getConfig('database');

                RefreshDatabaseState::$temporaryDatabase = Testing::addTokenIfNeeded($name);

                Schema::dropDatabaseIfExists(RefreshDatabaseState::$temporaryDatabase);
                Schema::createDatabase(RefreshDatabaseState::$temporaryDatabase);
            });
        })->app->flush();
    }

    /**
     * Drop the temporary database, if any.
     *
     * @afterClass
     *
     * @return void
     */
    public static function tearDownTemporaryDatabase()
    {
        if (RefreshDatabaseState::$temporaryDatabase) {
            tap(new static(), function ($testCase) {
                $testCase->refreshApplication();

                Schema::dropDatabaseIfExists(
                    RefreshDatabaseState::$temporaryDatabase,
                );
            })->app->flush();
        }
    }

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate', $this->migrateUsing());

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * The parameters that should be used when running "migrate".
     *
     * @return array
     */
    protected function migrateUsing()
    {
        return [
            '--seed' => $this->shouldSeed(),
        ];
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * The parameters that should be used when running "migrate:fresh".
     *
     * @return array
     */
    protected function migrateFreshUsing()
    {
        return [
            '--drop-views' => $this->shouldDropViews(),
            '--drop-types' => $this->shouldDropTypes(),
            '--seed' => $this->shouldSeed(),
        ];
    }

    /**
     * Begin a database transaction on the testing database.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollback();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }

    /**
     * The database connections that should have transactions.
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
                            ? $this->connectionsToTransact : [null];
    }

    /**
     * Determine if views should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropViews()
    {
        return property_exists($this, 'dropViews') ? $this->dropViews : false;
    }

    /**
     * Determine if types should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropTypes()
    {
        return property_exists($this, 'dropTypes') ? $this->dropTypes : false;
    }

    /**
     * Determine if the seed task should be run when refreshing the database.
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return property_exists($this, 'seed') ? $this->seed : false;
    }
}
