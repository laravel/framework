<?php

namespace Illuminate\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

abstract class MigrationCreatorCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new table command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * The table to migrate.
     *
     * @return string
     */
    abstract public function migrationTableName();

    /**
     * The location where is the migration file.
     *
     * @return string
     */
    abstract public function migrationStubPath();

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $table = $this->migrationTableName();
        $tableClassName = Str::studly($table);

        $fullPath = $this->createBaseMigration($table);
        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'],
            [$table, $tableClassName],
            $this->files->get(
                $this->migrationStubPath()
            )
        );

        $this->files->put($fullPath, $stub);

        $this->info('Migration created successfully!');

        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the table.
     *
     * @param  string  $table
     * @return string
     */
    protected function createBaseMigration($table)
    {
        $name = 'create_'.$table.'_table';

        $path = $this->laravel->databasePath().'/migrations';

        return $this->laravel['migration.creator']->create($name, $path);
    }
}
