<?php

namespace Illuminate\Queue\Console;

use Illuminate\Database\Console\MigrationCreatorCommand;

class TableCommand extends MigrationCreatorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the queue jobs database table';

    /**
     * @return string
     */
    public function migrationTableName()
    {
        return $this->laravel['config']->get('queue.connections.database.table', 'jobs');
    }

    /**
     * @return string
     */
    public function migrationStubPath()
    {
        return __DIR__.'/stubs/jobs.stub';
    }
}
