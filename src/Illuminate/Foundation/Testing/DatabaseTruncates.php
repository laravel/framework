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
        // Always remove any test data before the application is destroyed.
        $this->beforeApplicationDestroyed(fn () => $this->truncateTables());

        // Migrate and seed the database on first run.
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;

            return;
        }

        // Seed the database on subsequent runs.
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

    protected function truncateTables(): void
    {
        /** @var \Illuminate\Database\DatabaseManager $database */
        $database = $this->app->make('db');

        foreach ($this->connectionsToTruncate() as $name) {
            $connection = $database->connection($name);

            $connection->getSchemaBuilder()->disableForeignKeyConstraints();

            $dispatcher = $connection->getEventDispatcher();
            $connection->unsetEventDispatcher();

            $this->truncateTablesForConnection($connection, $name);

            $connection->setEventDispatcher($dispatcher);
            $connection->disconnect();
        }
    }

    // Truncate all tables for a given connection.
    protected function truncateTablesForConnection(ConnectionInterface $connection, ?string $name): void
    {
        collect(static::$allTables[$name] ??= $connection->getDoctrineSchemaManager()->listTableNames())
            ->diff($this->excludeTables($name))
            ->filter(fn ($table) => $connection->table($table)->exists())
            ->each(fn ($table) => $connection->table($table)->truncate());
    }

    // Get the tables that should not be truncated.
    protected function excludeTables(?string $connectionName): array
    {
        return match ($connectionName) {
            null => [$this->app['config']->get('database.migrations')],
            default => [],
        };
    }

    // The database connections that should be truncated.
    protected function connectionsToTruncate(): array
    {
        return property_exists($this, 'connectionsToTruncate')
            ? $this->connectionsToTruncate : [null];
    }
}
