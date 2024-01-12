<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\MigrationGeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Filesystem\join_paths;

#[AsCommand(name: 'make:queue-table')]
class TableCommand extends MigrationGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:queue-table';

    /**
     * The console command name aliases.
     *
     * @var array
     */
    protected $aliases = ['queue:table'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the queue jobs database table';

    /**
     * Get the migration table name.
     *
     * @return string
     */
    protected function migrationTableName()
    {
        return $this->laravel['config']['queue.connections.database.table'];
    }

    /**
     * Get the path to the migration stub file.
     *
     * @return string
     */
    protected function migrationStubFile()
    {
        return __DIR__.'/stubs/jobs.stub';
    }

    /**
     * Determine whether a migration for the table already exists.
     *
     * @param  string  $table
     * @return bool
     */
    protected function migrationExists($table)
    {
        if ($table !== 'jobs') {
            return parent::migrationExists($table);
        }

        return count($this->files->glob(sprintf(
            '{%s,%s}',
            join_paths($this->laravel->databasePath('migrations'), '*_*_*_*_create_'.$table.'_table.php'),
            join_paths($this->laravel->databasePath('migrations'), '0001_01_01_000002_create_jobs_table.php'),
        ))) !== 0;
    }
}
