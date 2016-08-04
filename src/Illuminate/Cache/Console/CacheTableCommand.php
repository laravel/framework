<?php

namespace Illuminate\Cache\Console;

use Illuminate\Console\MigrationCreatorCommand;

class CacheTableCommand extends MigrationCreatorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the cache database table';

    /**
     * @return string
     */
    public function migrationTableName()
    {
        return $this->laravel['config']->get('cache.stores.database.table', 'cache');
    }

    /**
     * @return string
     */
    public function migrationStubPath()
    {
        return __DIR__.'/stubs/cache.stub';
    }
}
