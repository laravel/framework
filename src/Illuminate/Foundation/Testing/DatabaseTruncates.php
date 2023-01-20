<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;

trait DatabaseTruncates
{
    use CanConfigureMigrationCommands;

    protected static array $allTables;

    protected function runDatabaseTruncates(): void
    {
        // Migrate and seed the database on first run.
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;

            return;
        }

        // Always clear any test data on subsequent runs.
        $this->clearPreviousTestData();

        if ($seeder = $this->seeder()) {
            // Use a specific seeder class.
            $this->artisan('db:seed', ['--class' => $seeder]);

            return;
        }

        if ($this->shouldSeed()) {
            // Use the default seeder class.
            $this->artisan('db:seed');
        }
    }

    protected function clearPreviousTestData(): void
    {
        /** @var \Illuminate\Database\DatabaseManager $database */
        $database = $this->app->make('db');

        collect($this->connectionsToTruncate())
            ->each(function ($name) use ($database) {
                $connection = $database->connection($name);

                if (! $this->useForeignKeyChecks($name)) {
                    $this->truncateTablesForConnection($connection, $name);

                    return;
                }

                $connection->getSchemaBuilder()->withoutForeignKeyConstraints(
                    fn () => $this->truncateTablesForConnection($connection, $name)
                );
            });
    }

    // Truncate all tables for a given connection.
    protected function truncateTablesForConnection(ConnectionInterface $connection, ?string $name): void
    {
        $dispatcher = $connection->getEventDispatcher();
        $connection->unsetEventDispatcher();

        collect(static::$allTables[$name] ??= $connection->getDoctrineSchemaManager()->listTableNames())
            ->diff($this->excludeTables($name))
            ->filter(fn ($table) => $connection->table($table)->exists())
            ->each(fn ($table) => $connection->table($table)->truncate());

        $connection->setEventDispatcher($dispatcher);
    }

    // Get the tables that should not be truncated.
    protected function excludeTables(?string $connectionName): array
    {
        if (property_exists($this, 'excludeTables')) {
            return $this->excludeTables[$connectionName] ?? [];
        }

        return [$this->app['config']->get('database.migrations')];
    }

    // Should foreign key checks be enabled after truncating tables?
    protected function useForeignKeyChecks(?string $connectionName): bool
    {
        if (property_exists($this, 'useForeignKeyChecks')) {
            return $this->useForeignKeyChecks[$connectionName] ?? true;
        }

        return true;
    }

    // The database connections that should be truncated.
    protected function connectionsToTruncate(): array
    {
        return property_exists($this, 'connectionsToTruncate')
            ? $this->connectionsToTruncate : [null];
    }
}
