<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;

class PostgresSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $path
     * @return void
     */
    public function dump(Connection $connection, $path)
    {
        $excludedTables = collect($connection->getSchemaBuilder()->getAllTables())
                        ->map->tablename
                        ->reject(function ($table) {
                            return $table === $this->migrationTable;
                        })->map(function ($table) {
                            return '--exclude-table-data="*.'.$table.'"';
                        })->implode(' ');

        $this->makeProcess(
            $this->baseDumpCommand().' --file=$LARAVEL_LOAD_PATH '.$excludedTables
        )->mustRun($this->output, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $command = 'PGPASSWORD=$LARAVEL_LOAD_PASSWORD pg_restore --no-owner --no-acl --clean --if-exists --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --username=$LARAVEL_LOAD_USER --dbname=$LARAVEL_LOAD_DATABASE $LARAVEL_LOAD_PATH';

        if (Str::endsWith($path, '.sql')) {
            $command = 'PGPASSWORD=$LARAVEL_LOAD_PASSWORD psql --file=$LARAVEL_LOAD_PATH --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --username=$LARAVEL_LOAD_USER --dbname=$LARAVEL_LOAD_DATABASE';
        }

        $process = $this->makeProcess($command);

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base dump command arguments for PostgreSQL as a string.
     *
     * @return string
     */
    protected function baseDumpCommand()
    {
        return 'PGPASSWORD=$LARAVEL_LOAD_PASSWORD pg_dump --no-owner --no-acl -Fc --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --username=$LARAVEL_LOAD_USER $LARAVEL_LOAD_DATABASE';
    }

    /**
     * Get the base variables for a dump / load command.
     *
     * @param  array  $config
     * @return array
     */
    protected function baseVariables(array $config)
    {
        return [
            'LARAVEL_LOAD_HOST' => $config['host'],
            'LARAVEL_LOAD_PORT' => $config['port'],
            'LARAVEL_LOAD_USER' => $config['username'],
            'LARAVEL_LOAD_PASSWORD' => $config['password'],
            'LARAVEL_LOAD_DATABASE' => $config['database'],
        ];
    }
}
