<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Str;

class MySqlSchemaState extends SchemaState
{
    /**
     * Keep track if it is needed to turn on the statistics.
     * @var bool
     */
    public $columnStatisticsOff = false;

    /**
     * Make the process and run it for dumping the schema.
     * @param $path
     */
    private function makeDumpProcess($path)
    {
        $this->makeProcess(
            $this->baseDumpCommand().' --routines --result-file=$LARAVEL_LOAD_PATH --no-data'
        )->mustRun($this->output, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * @param \Exception $e
     * @return bool
     */
    private function isColumnStatisticsIssue(\Exception $e)
    {
        return Str::contains($e->getMessage(), 'column_statistics');
    }

    /**
     * Dump the database's schema into a file.
     *
     * @param  string  $path
     * @return void
     */
    public function dump($path)
    {
        try {
            $this->makeDumpProcess($path);
        } catch (\Exception $e) {
            if ($this->isColumnStatisticsIssue($e)) {
                $this->columnStatisticsOff = true;
                $this->makeDumpProcess($path);
            }
        }

        $this->removeAutoIncrementingState($path);

        $this->appendMigrationData($path);
    }

    /**
     * Remove the auto-incrementing state from the given schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function removeAutoIncrementingState(string $path)
    {
        $this->files->put($path, preg_replace(
            '/\s+AUTO_INCREMENT=[0-9]+/iu',
            '',
            $this->files->get($path)
        ));
    }

    /**
     * @return \Symfony\Component\Process\Process
     */
    private function makeMigrationProcess()
    {
        with($process = $this->makeProcess(
            $this->baseDumpCommand().' migrations --no-create-info --skip-extended-insert --skip-routines --compact'
        ))->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            //
        ]));
        return $process;
    }
    /**
     * Append the migration data to the schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        $process = null;
        try {
            $process = $this->makeMigrationProcess();
        } catch (\Exception $e) {
            if ($this->isColumnStatisticsIssue($e) && ! $this->columnStatisticsOff) {
                $this->columnStatisticsOff = true;
                $process = $this->makeMigrationProcess();
            }
        }
        if (! is_null($process)) {
            $this->files->append($path, $process->getOutput());
        }
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $process = $this->makeProcess('mysql --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --user=$LARAVEL_LOAD_USER --password=$LARAVEL_LOAD_PASSWORD --database=$LARAVEL_LOAD_DATABASE < $LARAVEL_LOAD_PATH');

        $process->mustRun(null, array_merge($this->baseVariables($this->connection->getConfig()), [
            'LARAVEL_LOAD_PATH' => $path,
        ]));
    }

    /**
     * Get the base dump command arguments for MySQL as a string.
     *
     * @return string
     */
    protected function baseDumpCommand()
    {
        $gtidPurged = $this->connection->isMaria() ? '' : '--set-gtid-purged=OFF';
        $columnStatisticsOption = $this->columnStatisticsOff ? ' --column-statistics=0' : '';

        return 'mysqldump '.$gtidPurged.$columnStatisticsOption.' --skip-add-drop-table --skip-add-locks --skip-comments --skip-set-charset --tz-utc --host=$LARAVEL_LOAD_HOST --port=$LARAVEL_LOAD_PORT --user=$LARAVEL_LOAD_USER --password=$LARAVEL_LOAD_PASSWORD $LARAVEL_LOAD_DATABASE';
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
