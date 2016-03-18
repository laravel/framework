<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Illuminate\Filesystem\Filesystem;

class FailedTableCommand extends Command
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
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    /**
     * Create a new failed queue jobs table command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $fullPath = $this->createBaseMigration();

        $table = $this->laravel['config']['queue.failed.table'];

        $stub = str_replace(
            '{{table}}', $table, $this->files->get(__DIR__.'/stubs/failed_jobs.stub')
        );

        $this->files->put($fullPath, $stub);

        $this->info('Migration created successfully!');

        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the table.
     *
     * @return string
     */
    protected function createBaseMigration()
    {
        $name = 'create_failed_jobs_table';

        $path = $this->laravel->databasePath().'/migrations';

        return $this->laravel['migration.creator']->create($name, $path);
    }
}
