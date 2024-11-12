<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Connection;

class DmSchemaState extends SchemaState
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
        $command = $this->baseDumpCommand().' FILE="${:LARAVEL_LOAD_PATH}" ROWS=N NOLOG=Y DUMMY=Y';
        $variables = array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]);
        $this->makeProcess($command)->mustRun($this->output, $variables);
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        if ($path == 'schema/dm-schema.sql') {
            $path = 'schema/dm-schema.dmp';
        }

        $command = 'dimp "${:LARAVEL_LOAD_USER}"/"${:LARAVEL_LOAD_PASSWORD}"@"${:LARAVEL_LOAD_HOST}":"${:LARAVEL_LOAD_PORT}" FILE="${:LARAVEL_LOAD_PATH}"';

        $process = $this->makeProcess($command);

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base dump command arguments as a string.
     *
     * @return string
     */
    protected function baseDumpCommand()
    {
        return 'dexp "${:LARAVEL_LOAD_USER}"/"${:LARAVEL_LOAD_PASSWORD}"@"${:LARAVEL_LOAD_HOST}":"${:LARAVEL_LOAD_PORT}" SCHEMAS="${:LARAVEL_LOAD_SCHEMA}"';
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
            'LARAVEL_LOAD_PORT' => $config['port'] ?? '5236',
            'LARAVEL_LOAD_USER' => $config['username'],
            'LARAVEL_LOAD_PASSWORD' => $config['password'],
            'LARAVEL_LOAD_SCHEMA' => $config['schema'],
        ];
    }
}
