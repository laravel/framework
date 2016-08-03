<?php

namespace Illuminate\Queue\Console;

use Illuminate\Database\Console\MigrationCreatorCommand;

class FailedTableCommand extends MigrationCreatorCommand
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
     * @return string
     */
    public function migrationTableName()
    {
        return $this->laravel['config']->get('queue.failed.table', 'failed_jobs');
    }

    /**
     * @return string
     */
    public function migrationStubPath()
    {
        return __DIR__.'/stubs/failed_jobs.stub';
    }
}
