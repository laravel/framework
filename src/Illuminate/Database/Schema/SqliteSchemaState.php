<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

class SqliteSchemaState extends SchemaState
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
        $process = $this->makeProcess($this->baseCommand().' ".schema --indent"')
            ->setTimeout(null)
            ->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
                //
            ]));

        $migrations = preg_replace('/CREATE TABLE sqlite_.+?\);[\r\n]+/is', '', $process->getOutput());

        $migrations = $this->removeShadowTables($migrations);

        $this->files->put($path, $migrations.PHP_EOL);

        if ($this->hasMigrationTable()) {
            $this->appendMigrationData($path);
        }
    }

    /**
     * Remove the shadow tables of virtual tables from the given schema dump.
     *
     * @param  string  $schema
     * @return string
     */
    protected function removeShadowTables(string $schema)
    {
        $shadowTables = (new Collection(
            $this->connection->selectFromWriteConnection('pragma main.table_list')
        ))->where('type', 'shadow')->pluck('name');

        foreach ($shadowTables as $name) {
            $schema = preg_replace('/^CREATE TABLE IF NOT EXISTS ([\'"])'.preg_quote($name, '/').'\1[^;]*+;\R/m', '', $schema);
        }

        return $schema;
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        $process = $this->makeProcess(
            $this->baseCommand().' ".dump \''.$this->getMigrationTable().'\'"'
        )->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            //
        ]));

        $migrations = (new Collection(preg_split("/\r\n|\n|\r/", $process->getOutput())))
            ->filter(fn ($line) => preg_match('/^\s*(--|INSERT\s)/iu', $line) === 1 && $line !== '')
            ->all();

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
        $database = $this->connection->getDatabaseName();

        if ($database === ':memory:' ||
            str_contains($database, '?mode=memory') ||
            str_contains($database, '&mode=memory')
        ) {
            $this->connection->getPdo()->exec($this->files->get($path));

            return;
        }

        $process = $this->makeProcess($this->baseCommand().' < "${:LARAVEL_LOAD_PATH}"');

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
        return 'sqlite3 "${:LARAVEL_LOAD_DATABASE}"';
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
            'LARAVEL_LOAD_DATABASE' => $config['database'],
        ];
    }
}
