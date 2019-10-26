<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Finder\SplFileInfo;

class DatabaseState
{
    /**
     * Key to cache the last modified migration timestamp.
     *
     * @var string
     */
    const LAST_MODIFIED_KEY = 'database_last_modified_migration';

    /**
     * Flag to indicate if the test database has been migrated recently.
     *
     * @var bool
     */
    private static $recentlyMigrated = false;

    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Determine if the migrations should be run.
     *
     * @return bool
     */
    public function needsRefresh()
    {
        if (static::$recentlyMigrated) {
            return false;
        }

        if ($this->cache()->get(self::LAST_MODIFIED_KEY) == $this->lastModifiedMigration()) {
            return false;
        }

        return true;
    }

    /**
     * Mark the database as migrated or not and cache or forget the last modified migration timestamp.
     *
     * @return void
     */
    public function markAsMigrated($migrated = true)
    {
        if ($migrated) {
            $this->cache()->put(self::LAST_MODIFIED_KEY, $this->lastModifiedMigration());
        } else {
            $this->cache()->forget(self::LAST_MODIFIED_KEY);
        }

        static::$recentlyMigrated = $migrated;
    }

    /**
     * Fetchs a file cache repository from the IoC container.
     *
     * @return \Illuminate\Cache\Repository
     */
    protected function cache()
    {
        return $this->app['cache']->store('file');
    }

    /**
     * Returns the timestamp of the last modified migration.
     *
     * @return int
     */
    protected function lastModifiedMigration()
    {
        return collect($this->migrationPaths())
            ->flatMap(function ($dir) {
                return $this->app['files']->files($dir);
            })
            ->max(function (SplFileInfo $file) {
                return $file->getMTime();
            });
    }

    /**
     * Get the all migration paths.
     *
     * @return array
     */
    protected function migrationPaths()
    {
        return array_merge(
            $this->app['migrator']->paths(),
            [$this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations']
        );
    }
}
