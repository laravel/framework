<?php

namespace Illuminate\Database\Migrations;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Schema\PostgresBuilder;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database migration repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $table
     */
    public function __construct(Resolver $resolver, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
    }

    /**
     * Get the completed migrations.
     *
     * @return string[]
     */
    public function getRan()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration')->all();
    }

    /**
     * Get the list of migrations.
     *
     * @param  int  $steps
     * @return object{id: int, migration: string, batch: int}[]
     */
    public function getMigrations($steps)
    {
        $query = $this->table()->where('batch', '>=', '1');

        return $query->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->limit($steps)
            ->get()
            ->all();
    }

    /**
     * Get the list of the migrations by batch number.
     *
     * @param  int  $batch
     * @return object{id: int, migration: string, batch: int}[]
     */
    public function getMigrationsByBatch($batch)
    {
        return $this->table()
            ->where('batch', $batch)
            ->orderBy('migration', 'desc')
            ->get()
            ->all();
    }

    /**
     * Get the last migration batch.
     *
     * @return object{id: int, migration: string, batch: int}[]
     */
    public function getLast()
    {
        $query = $this->table()->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('migration', 'desc')->get()->all();
    }

    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return array<string, int>
     */
    public function getMigrationBatches()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('batch', 'migration')->all();
    }

    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int  $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['migration' => $file, 'batch' => $batch];

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param  object{id?: int, migration: string, batch?: int}  $migration
     * @return void
     */
    public function delete($migration)
    {
        $this->table()->where('migration', $migration->migration)->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->max('batch');
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $connection = $this->getConnection();

        $schema = $connection->getSchemaBuilder();

        if ($schema instanceof PostgresBuilder) {
            $this->ensurePostgresSchemaExists($connection, $schema);
        }

        $schema->create($this->table, function ($table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Create the first schema in the Postgres "search_path" if it does not exist.
     *
     * When a non-default "search_path" is configured and the schema has not been
     * created yet, creating the migration repository fails because Postgres has
     * no schema to create the table in. The schema is only created when it is
     * missing, so existing setups are left untouched.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\PostgresBuilder  $schema
     * @return void
     */
    protected function ensurePostgresSchemaExists(Connection $connection, PostgresBuilder $schema)
    {
        $schemas = $schema->getCurrentSchemaListing();

        if (empty($schemas)) {
            return;
        }

        $name = $schemas[0];

        $exists = $connection->scalar(
            'select exists(select 1 from pg_namespace where nspname = ?)', [$name], false
        );

        if ($exists) {
            return;
        }

        $connection->statement(
            'create schema if not exists '.$connection->getQueryGrammar()->wrap($name)
        );
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Delete the migration repository data store.
     *
     * @return void
     */
    public function deleteRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->drop($this->table);
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
     *
     * @param  string  $name
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}
