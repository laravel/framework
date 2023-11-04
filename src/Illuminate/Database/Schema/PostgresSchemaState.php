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
        $commands = collect([
            $this->baseDumpCommand().' --schema-only > '.$path,
            $this->baseDumpCommand().' -t '.$this->migrationTable.' --data-only >> '.$path,
        ]);

        $commands->map(function ($command, $path) {
            $this->makeProcess($command)->mustRun($this->output, array_merge($this->baseVariables($this->connection->getConfig()), [
                'LARAVEL_LOAD_PATH' => $path,
            ]));
        });
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $command = $this->binPath().'pg_restore --no-owner --no-acl --clean --if-exists --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --username="${:LARAVEL_LOAD_USER}" --dbname="${:LARAVEL_LOAD_DATABASE}" "${:LARAVEL_LOAD_PATH}"';

        if (str_ends_with($path, '.sql')) {
            $command = $this->binPath().'psql --file="${:LARAVEL_LOAD_PATH}" --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --username="${:LARAVEL_LOAD_USER}" --dbname="${:LARAVEL_LOAD_DATABASE}"';
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
        return $this->binPath().'pg_dump --no-owner --no-acl --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --username="${:LARAVEL_LOAD_USER}" --dbname="${:LARAVEL_LOAD_DATABASE}"';
    }

    /**
     * Get the base variables for a dump / load command.
     *
     * @param  array  $config
     * @return array
     */
    protected function baseVariables(array $config)
    {
        $config['host'] ??= '';

        return [
            'LARAVEL_LOAD_HOST' => is_array($config['host']) ? $config['host'][0] : $config['host'],
            'LARAVEL_LOAD_PORT' => $config['port'] ?? '',
            'LARAVEL_LOAD_USER' => $config['username'],
            'PGPASSWORD' => $config['password'],
            'LARAVEL_LOAD_DATABASE' => $config['database'],
        ];
    }

    /**
     * Get the bin path for the dump / restore command.
     *
     * @return string
     */
    protected function binPath()
    {
        $binPath = $this->connection->getConfig()['bin'] ?? '';

        if ($binPath) {
            $binPath = Str::finish($binPath, DIRECTORY_SEPARATOR);
        }

        return $binPath;
    }
}
