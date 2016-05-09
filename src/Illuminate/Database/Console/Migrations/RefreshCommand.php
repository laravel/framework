<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class RefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'migrate:refresh {--database= : The database connection to use.}
    {--force : Force the operation to run when in production.}
    {--path= : The path of migrations files to be executed.}
    {--seed : Indicates if the seed task should be re-run.}
    {--seeder : The class name of the root seeder.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $database = $this->option('database');

        $force = $this->option('force');

        $path = $this->option('path');

        $this->call('migrate:reset', [
            '--database' => $database, '--force' => $force,
        ]);

        // The refresh command is essentially just a brief aggregate of a few other of
        // the migration commands and just provides a convenient wrapper to execute
        // them in succession. We'll also see if we need to re-seed the database.
        $this->call('migrate', [
            '--database' => $database,
            '--force' => $force,
            '--path' => $path,
        ]);

        if ($this->needsSeeding()) {
            $this->runSeeder($database);
        }
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed') || $this->option('seeder');
    }

    /**
     * Run the database seeder command.
     *
     * @param  string  $database
     * @return void
     */
    protected function runSeeder($database)
    {
        $class = $this->option('seeder') ?: 'DatabaseSeeder';

        $force = $this->option('force');

        $this->call('db:seed', [
            '--database' => $database, '--class' => $class, '--force' => $force,
        ]);
    }
}
