<?php

namespace Illuminate\Database\Schema;

class MySqlSchemaState extends SchemaState
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
            $this->baseDumpCommand($includeData) . ' --routines --result-file=$LARAVEL_LOAD_PATH',
            )->mustRun(
                $this->output,
                array_merge($this->baseVariables($this->connection->getConfig()), [
                'LARAVEL_LOAD_PATH' => $path,
            ]),
                );

        if (!$includeData) {
            $this->removeAutoIncrementingState($path);

            $this->appendMigrationData($path, $includeData);
        }
    }

    /**
     * Remove the auto-incrementing state from the given schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function removeAutoIncrementingState(string $path)
    {
        $this->files->put($path, preg_replace('/\s+AUTO_INCREMENT=[0-9]+/iu', '', $this->files->get($path)));
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        with(
            $process = $this->makeProcess(
                $this->baseDumpCommand(true) .
                    ' migrations --no-create-info --skip-extended-insert --skip-routines --compact',
                ),
            )->mustRun(
                null,
                array_merge($this->baseVariables($this->connection->getConfig()), [
                //
            ]),
                );

        $this->files->append($path, $process->getOutput());
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $process = $this->makeProcess(
            'mysql --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --user=$LARAVEL_LOAD_USER --password=$LARAVEL_LOAD_PASSWORD --database=$LARAVEL_LOAD_DATABASE < $LARAVEL_LOAD_PATH',
            );

        $process->mustRun(
            null,
            array_merge($this->baseVariables($this->connection->getConfig()), [
                'LARAVEL_LOAD_PATH' => $path,
            ]),
            );
    }

    /**
     * Get the base dump command arguments for MySQL as a string.
     *
     * @param  bool  $includeData
     * @return string
     */
    protected function baseDumpCommand(bool $includeData)
    {
        $cmd =
            'mysqldump --set-gtid-purged=OFF --skip-add-drop-table --skip-add-locks --skip-comments --skip-set-charset --tz-utc --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --user=$LARAVEL_LOAD_USER --password=$LARAVEL_LOAD_PASSWORD $LARAVEL_LOAD_DATABASE';

        if (!$includeData) {
            $cmd .= ' --no-data';
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
