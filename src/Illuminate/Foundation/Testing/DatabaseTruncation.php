<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use Illuminate\Support\Collection;

trait DatabaseTruncation
{
    use CanConfigureMigrationCommands;

    /**
     * The cached names of the database tables for each connection.
     *
     * @var array
     */
    protected static array $allTables;

    /**
     * Truncate the database tables for all configured connections.
     *
     * @return void
     */
    protected function truncateDatabaseTables(): void
    {
        $this->beforeTruncatingDatabase();

        // Migrate and seed the database on first run...
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;

            return;
        }

        // Always clear any test data on subsequent runs...
        $this->truncateTablesForAllConnections();

        if ($seeder = $this->seeder()) {
            // Use a specific seeder class...
            $this->artisan('db:seed', ['--class' => $seeder]);
        } elseif ($this->shouldSeed()) {
            // Use the default seeder class...
            $this->artisan('db:seed');
        }

        $this->afterTruncatingDatabase();
    }

    /**
     * Truncate the database tables for all configured connections.
     *
     * @return void
     */
    protected function truncateTablesForAllConnections(): void
    {
        $database = $this->app->make('db');

        (new Collection($this->connectionsToTruncate()))
            ->each(function ($name) use ($database) {
                $connection = $database->connection($name);

                $connection->getSchemaBuilder()->withoutForeignKeyConstraints(
                    fn () => $this->truncateTablesForConnection($connection, $name)
                );
            });
    }

    /**
     * Truncate the database tables for the given database connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string|null  $name
     * @return void
     */
    protected function truncateTablesForConnection(ConnectionInterface $connection, ?string $name): void
    {
        $dispatcher = $connection->getEventDispatcher();

        $connection->unsetEventDispatcher();
        $schema = $connection->getSchemaBuilder();

        (new Collection(static::$allTables[$name] ??= $schema->getTables()))
            ->when(
                $schema instanceof PostgresBuilder ? $schema->getSchemas() : null,
                fn ($tables, $schemas) => $tables->filter(fn ($table) => in_array($table['schema'], $schemas))
            )
            ->when(
                property_exists($this, 'tablesToTruncate'),
                fn (Collection $tables) => $tables->intersectUsing(
                    $this->tablesToTruncate,
                    $this->compareTablesWithSchema(...)
                ),
                fn (Collection $tables) => $tables->diffUsing(
                    $this->exceptTables($name),
                    $this->compareTablesWithSchema(...)
                )
            )
            ->filter(fn ($table) => $connection->table(new Expression($table))->exists())
            ->each(fn ($table) => $connection->table(new Expression($table))->truncate());

        $connection->setEventDispatcher($dispatcher);
    }

    /**
     * Compare the given tables with or without schema.
     *
     * @param  array  $firstTable
     * @param  string  $secondTable
     * @return int
     */
    protected function compareTablesWithSchema(array $firstTable, string $secondTable): int
    {
        return $firstTable['schema'] && str_contains($secondTable, '.')
            ? $firstTable['schema'].'.'.$firstTable['name'] <=> $secondTable
            : $firstTable['name'] <=> $secondTable;
    }

    /**
     * The database connections that should have their tables truncated.
     *
     * @return array
     */
    protected function connectionsToTruncate(): array
    {
        return property_exists($this, 'connectionsToTruncate')
                    ? $this->connectionsToTruncate : [null];
    }

    /**
     * Get the tables that should not be truncated.
     *
     * @param  string|null  $connectionName
     * @return array
     */
    protected function exceptTables(?string $connectionName): array
    {
        $migrations = $this->app['config']->get('database.migrations');

        $migrationsTable = is_array($migrations) ? ($migrations['table'] ?? null) : $migrations;

        if (property_exists($this, 'exceptTables')) {
            if (array_is_list($this->exceptTables ?? [])) {
                return array_merge(
                    $this->exceptTables ?? [],
                    [$migrationsTable],
                );
            }

            return array_merge(
                $this->exceptTables[$connectionName] ?? [],
                [$migrationsTable],
            );
        }

        return [$migrationsTable];
    }

    /**
     * Perform any work that should take place before the database has started truncating.
     *
     * @return void
     */
    protected function beforeTruncatingDatabase(): void
    {
        //
    }

    /**
     * Perform any work that should take place once the database has finished truncating.
     *
     * @return void
     */
    protected function afterTruncatingDatabase(): void
    {
        //
    }
}
