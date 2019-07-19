<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Support\Stopwatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class SeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Timer instance.
     *
     * @var \Illuminate\Support\Stopwatch
     */
    protected $watch;

    /**
     * Create a new database seed command instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param \Illuminate\Support\Stopwatch $watch
     */
    public function __construct(Resolver $resolver, Stopwatch $watch)
    {
        parent::__construct();

        $this->resolver = $resolver;
        $this->watch = $watch;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->resolver->setDefaultConnection($this->getDatabase());

        $this->watch->start('seeder');

        Model::unguarded(function () {
            $this->getSeeder()->__invoke();
        });

        $this->info("Database seeding completed successfully in {$this->watch->check('seeder')} seconds.");
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->laravel->make($this->input->getOption('class'));

        return $class->setContainer($this->laravel)->setCommand($this)->setWatch($this->watch);
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }

    public function getWatch()
    {
        return $this->watch;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'],

            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
