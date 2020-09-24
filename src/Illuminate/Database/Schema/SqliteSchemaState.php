<?php

namespace Illuminate\Database\Schema;

class SqliteSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param string $path
     *
     * @return void
     */
    public function dump($path)
    {
        with($process = $this->makeProcess(
            $this->baseCommand().' .schema'
        ))->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            //
        ]));

        $migrations = collect(preg_split("/\r\n|\n|\r/", $process->getOutput()))->filter(function ($line) {
            return stripos($line, 'sqlite_sequence') === false &&
                   strlen($line) > 0;
        })->all();

        $this->files->put($path, implode(PHP_EOL, $migrations).PHP_EOL);

        $this->appendMigrationData($path);
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        with($process = $this->makeProcess(
            $this->baseCommand().' ".dump \'migrations\'"'
        ))->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            //
        ]));

        $migrations = collect(preg_split("/\r\n|\n|\r/", $process->getOutput()))->filter(function ($line) {
            return preg_match('/^\s*(--|INSERT\s)/iu', $line) === 1 &&
                   strlen($line) > 0;
        })->all();

        $this->files->append($path, implode(PHP_EOL, $migrations).PHP_EOL);
    }

    /**
     * Load the given schema file into the database.
     *
     * @param string $path
     *
     * @return void
     */
    public function load($path)
    {
        $process = $this->makeProcess($this->baseCommand().' < $LARAVEL_LOAD_PATH');

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base sqlite command arguments as a string.
     *
     * @return string
     */
    protected function baseCommand()
    {
        return 'sqlite3 $LARAVEL_LOAD_DATABASE';
    }

    /**
     * Get the base variables for a dump / load command.
     *
     * @return array
     */
    protected function baseVariables(array $config)
    {
        return [
            'LARAVEL_LOAD_DATABASE' => $config['database'],
        ];
    }
}
