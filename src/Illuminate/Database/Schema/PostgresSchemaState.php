<?php

namespace Illuminate\Database\Schema;

class PostgresSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param  string  $path
     * @param  bool  $includeData
     * @return void
     */
    public function dump($path, bool $includeData = false)
    {
        $this->makeProcess(
            $this->baseDumpCommand($includeData).' --no-owner --file=$LARAVEL_LOAD_PATH'
        )->mustRun($this->output, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));

        if (!$includeData) {
            $this->appendMigrationData($path);
        }
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        with($process = $this->makeProcess(
            $this->baseDumpCommand(true).' --table=migrations --data-only --inserts'
        ))->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            //
        ]));

        $migrations = collect(preg_split("/\r\n|\n|\r/", $process->getOutput()))->filter(function ($line) {
            return preg_match('/^\s*(--|SELECT\s|SET\s)/iu', $line) === 0 &&
                   strlen($line) > 0;
        })->all();

        $this->files->append($path, implode(PHP_EOL, $migrations).PHP_EOL);
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $process = $this->makeProcess('PGPASSWORD=$LARAVEL_LOAD_PASSWORD psql --file=$LARAVEL_LOAD_PATH --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --username=$LARAVEL_LOAD_USER --dbname=$LARAVEL_LOAD_DATABASE');

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base dump command arguments for MySQL as a string.
     *
     * @return string
     */
    protected function baseDumpCommand($includeData)
    {
        $cmd = 'PGPASSWORD=$LARAVEL_LOAD_PASSWORD pg_dump --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --username=$LARAVEL_LOAD_USER $LARAVEL_LOAD_DATABASE';

        if (!$includeData) {
            $cmd .= ' --schema-only';
        }

        return $cmd;
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
