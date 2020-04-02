<?php

namespace Illuminate\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class MySqlDumper
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The output callable instance.
     *
     * @var callable
     */
    protected $output;

    /**
     * Create a new dumper instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  callable  $output
     * @return void
     */
    public function __construct(Filesystem $files, callable $output = null)
    {
        $this->files = $files;

        $this->output = $output ?: function () {
            //
        };
    }

    /**
     * Dump the given connection's schema into an SQL string.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $path
     * @return void
     */
    public function dump(Connection $connection, $path)
    {
        (new Process(array_merge($this->dumpCommand($connection->getConfig()), [
            '--routines',
            '--result-file='.$path,
            '--no-data',
        ])))->mustRun($this->output);

        $this->removeAutoIncrementingState($path);

        with($process = new Process(array_merge($this->dumpCommand($connection->getConfig()), [
            'migrations',
            '--no-create-info',
            '--skip-extended-insert',
            '--skip-routines',
            '--compact',
        ])))->mustRun();

        $this->files->append($path, $process->getOutput());
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
     * Get the dump command for MySQL as an array.
     *
     * @param  array  $config
     * @return array
     */
    protected function dumpCommand(array $config)
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
}
