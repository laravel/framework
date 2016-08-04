<?php

namespace Illuminate\Session\Console;

use Illuminate\Console\MigrationCreatorCommand;

class SessionTableCommand extends MigrationCreatorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'session:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the session database table';

    /**
     * @return string
     */
    public function migrationTableName()
    {
        return $this->laravel['config']->get('session.table', 'sessions');
    }

    /**
     * @return string
     */
    public function migrationStubPath()
    {
        return __DIR__.'/stubs/database.stub';
    }
}
