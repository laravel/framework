<?php

namespace Illuminate\Database\Console\Migrations;

class RedoCommand extends RefreshCommand
{
    /**
     * The console command name & signature.
     *
     * The redo command rolls back the last migration batch and re-runs it.
     *
     * We expose the same options as Refresh, but default --step to 1.
     *
     * @see RefreshCommand
     */
    protected $signature = 'migrate:redo
        {--database= : The database connection to use}
        {--force : Run without confirmation}
        {--path=* : Paths to migration files to be executed}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--seed : Re-run the database seeds}
        {--seeder= : The class name of the root seeder}
        {--step=1 : Number of migrations to be rolled back and re-run (default 1)}
        {--pretend : Dump the SQL instead of running the command}';

    protected $description = 'Rollback the last migration batch and immediately re-run it';

    public function handle(): int
    {
        // Ensure the default is always 1 when no --step supplied
        if (! $this->input->getOption('step')) {
            $this->input->setOption('step', 1);
        }

        return parent::handle();   // RefreshCommand already does the heavy lifting
    }
}
