<?php

namespace Illuminate\Foundation\Testing;

trait RefreshDatabaseWhenNecessary
{
    use RefreshDatabase {
        refreshTestDatabase as baseRefreshTestDatabase;
    }

    protected function refreshTestDatabase(): void
    {
        if (! $this->shouldMigrate()) {
            RefreshDatabaseState::$migrated = true;
        }

        $this->baseRefreshTestDatabase();
    }

    /**
     * Checks whether there are any migration files that have not yet run.
     *
     * @return bool
     */
    protected function shouldMigrate(): bool
    {
        $migrator = $this->app->make('migrator');

        if (! $migrator->repositoryExists()) {
            return true;
        }

        $migrationDirectories = array_merge($migrator->paths(), [database_path('migrations')]);
        $migrationFiles       = $migrator->getMigrationFiles($migrationDirectories);

        return count($migrationFiles) !== count($migrator->getRepository()->getRan());
    }
}
