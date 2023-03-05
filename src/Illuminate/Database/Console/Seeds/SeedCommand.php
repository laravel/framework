<?php

namespace Illuminate\Database\Console\Seeds;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:seed')]
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
     * Create a new database seed command instance.
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
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $this->components->info('Seeding database.');

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        //if rof (rollback on failure) is set, then start transaction and rollback in cause of a failure
        if ($this->hasOption('rof') && $this->option('rof')) {
            $this->seedWithRollback();
        } else {
            $this->seed();
        }

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        return 0;
    }

    /**
     * If the seeder triggers an error,
     * then do a rollback
     *
     * @return void
     * @throws Exception
     */
    protected function seedWithRollback(): void
    {
        $this->resolver->connection()?->beginTransaction();

        try {
            $this->seed();
            // Commit the transaction if no errors
            $this->resolver->connection()?->commit();
        } catch (Exception $e) {
            // Rollback the transaction if any errors
            $this->resolver->connection()?->rollBack();
            throw $e;
        }
    }

    /**
     * Execute the seeder
     *
     * @return void
     */
    protected function seed(): void
    {
        Model::unguarded(function () {
            $this->getSeeder()->__invoke();
        });
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->input->getArgument('class') ?? $this->input->getOption('class');

        if (! str_contains($class, '\\')) {
            $class = 'Database\\Seeders\\'.$class;
        }

        if ($class === 'Database\\Seeders\\DatabaseSeeder' &&
            ! class_exists($class)) {
            $class = 'DatabaseSeeder';
        }

        return $this->laravel->make($class)
                        ->setContainer($this->laravel)
                        ->setCommand($this);
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

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['class', InputArgument::OPTIONAL, 'The class name of the root seeder', null],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['rof', null, InputOption::VALUE_NONE, 'Rollback on failure - seed with a transaction and rollback in cause of a failure']
        ];
    }
}
