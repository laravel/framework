<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\MySqlConnection;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class MySqlSchemaState
{
    /**
     * The connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The process factory callback.
     *
     * @var callable
     */
    protected $processFactory;

    /**
     * The output callable instance.
     *
     * @var callable
     */
    protected $output;

    /**
     * Create a new dumper instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  callable  $processFactory
     * @return void
     */
    public function __construct(MySqlConnection $connection, Filesystem $files = null, callable $processFactory = null)
    {
        $this->connection = $connection;

        $this->files = $files ?: new Filesystem;

        $this->processFactory = $processFactory ?: function (...$arguments) {
            return new Process(...$arguments);
        };

        $this->handleOutputUsing(function () {
            //
        });
    }

    /**
     * Dump the given connection's schema into an SQL string.
     *
     * @param  string  $path
     * @return void
     */
    public function dump($path)
    {
        $this->makeProcess(array_merge($this->baseDumpCommand($this->connection->getConfig()), [
            '--routines',
            '--result-file='.$path,
            '--no-data',
        ]))->mustRun($this->output);

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
     * Append the migration data to the schema dump.
     *
     * @param  string  $path
     * @return void
     */
    protected function appendMigrationData(string $path)
    {
        with($process = $this->makeProcess(
            array_merge($this->baseDumpCommand($this->connection->getConfig()), [
                'migrations',
                '--no-create-info',
                '--skip-extended-insert',
                '--skip-routines',
                '--compact',
            ])
        ))->mustRun();

        $this->files->append($path, $process->getOutput());
    }

    /**
     * Get the base dump command arguments for MySQL as an array.
     *
     * @param  array  $config
     * @return array
     */
    protected function baseDumpCommand(array $config)
    {
        return [
            'mysqldump',
            '--set-gtid-purged=OFF',
            '--skip-add-drop-table',
            '--skip-add-locks',
            '--skip-comments',
            '--skip-set-charset',
            '--tz-utc',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--password='.$config['password'],
            $config['database'],
        ];
    }

    /**
     * Create a new process instance.
     *
     * @param  array  $arguments
     * @return \Symfony\Component\Process\Process
     */
    public function makeProcess(...$arguments)
    {
        return call_user_func($this->processFactory, ...$arguments);
    }

    /**
     * Specify the callback that should be used to handle process output.
     *
     * @param  callable  $output
     * @return $this
     */
    public function handleOutputUsing(callable $output)
    {
        $this->output = $output;

        return $this;
    }
}
