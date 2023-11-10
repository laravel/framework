<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\MigrationGeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:failed-table')]
class FailedTableCommand extends MigrationGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:failed-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the failed queue jobs database table';

    /**
     * Get the migration table name.
     *
     * @return string
     */
    protected function migrationTableName()
    {
        return $this->laravel['config']['queue.failed.table'];
    }

    /**
     * Get the path to the migration stub file.
     *
     * @return string
     */
    protected function migrationStubFile()
    {
        return __DIR__.'/stubs/failed_jobs.stub';
    }
}
