<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class EmptyCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:empty 
                {--database= : The database connection to use.}
                {--force : Force the operation to run when in production.}
                {--migrate : Indicates if the migrations should be re-run. }
                {--path= : The path of migrations files to be executed.}
                {--seed : Indicates if the seed task should be re-run.}
                {--class : The class of the seeder to use.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables from the database';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database empty command instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

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

        // Set the default database connection to be used
        $this->resolver->setDefaultConnection($this->getDatabase());

        // Load the correct table dropper class to use based upon the database driver
        if (! $tableDropper = $this->getTableDropper()) {
            return $this->error('Sorry - the "db:empty" command does not support "'.$this->resolver->connection()->getDriverName().'" database drivers at this stage.');
        }

        // Now drop all the tables from the selected database
        $this->info('Dropping all tables...');
        $tableDropper->dropAllTables($this->resolver->connection());
        
        // Run any additional commands the developer has asked for
        if ($this->needsMigrations()) {
            $this->runMigrations();
        }

        if ($this->needsSeeding()) {
            $this->runSeeder();
        }
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        return $this->input->getOption('database') ?: $this->laravel['config']['database.default'];
    }

    /**
     * Determine if the developer has requested migrations to run.
     *
     * @return bool
     */
    protected function needsMigrations()
    {
        return $this->input->getOption('migrate') || $this->input->getOption('path');
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->input->getOption('seed') || $this->input->getOption('class');
    }

    /**
     * Get the table dropper class for this driver
     *
     * @return void
     */
    protected function getTableDropper()
    {
        $driver = $this->resolver->connection()->getDriverName();

        $dropperClass = '\\Illuminate\\Database\\Console\\Migrations\\TableDroppers\\'.ucfirst($driver);
        
        if (! class_exists($dropperClass)) {
            return false;
        }

        return $this->laravel->make($dropperClass);
    }

    /**
     * Run the migration command.
     *
     * @return void
     */
    protected function runMigrations()
    {
        $this->info('Running migrations...');

        $this->call('migrate', [
            '--database' => $this->input->getOption('database'),
            '--path' => $this->input->getOption('path'),
            '--force' => true,
        ]);
    }

    /**
     * Run the database seeder command.
     *
     * @return void
     */
    protected function runSeeder()
    {
        $this->info('Running seeders...');

        $this->call('db:seed', [
            '--database' => $this->input->getOption('database'),
            '--class' => $this->input->getOption('class') ?: 'DatabaseSeeder',
            '--force' => true,
        ]);
    }

}
