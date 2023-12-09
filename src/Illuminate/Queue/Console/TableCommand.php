<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\MigrationGeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

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
}
