<?php

namespace Illuminate\Database\Schema;

use Symfony\Component\Process\Exception\ProcessFailedException;

class MariaDbSchemaState extends MySqlSchemaState
{
    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $versionInfo = $this->detectClientVersion();

        $command = 'mariadb '.$this->connectionString($versionInfo).' --database="${:LARAVEL_LOAD_DATABASE}" < "${:LARAVEL_LOAD_PATH}"';

        $process = $this->makeProcess($command)->setTimeout(null);

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base dump command arguments for MariaDB as a string.
     *
     * @return string
     */
    protected function baseDumpCommand()
    {
        $versionInfo = $this->detectClientVersion();

        $command = 'mariadb-dump '.$this->connectionString($versionInfo).' --no-tablespaces --skip-add-locks --skip-comments --skip-set-charset --tz-utc';

        return $command.' "${:LARAVEL_LOAD_DATABASE}"';
    }

    /**
     * Detect the MariaDB client version.
     *
     * @return array{version: string, isMariaDb: bool}
     */
    protected function detectClientVersion(): array
    {
        // Minimum version of MariaDB that supports the mariadb command...
        $version = '10.5.2';

        try {
            $versionOutput = $this->makeProcess('mariadb --version')->mustRun()->getOutput();

            if (preg_match('/(\d+\.\d+\.\d+)/', $versionOutput, $matches)) {
                $version = $matches[1];
            }
        } catch (ProcessFailedException) {
        }

        return [
            'isMariaDb' => true,
            'version' => $version,
        ];
    }
}
