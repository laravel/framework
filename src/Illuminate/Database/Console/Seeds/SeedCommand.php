<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:seed')]
class SeedCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

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
     */
    public function handle()
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $this->components->info('Seeding database.');

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        Model::unguarded(function () {
            $this->getSeeder()->__invoke();
        });

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        return 0;
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder |callable
     */
    protected function getSeeder()
    {
        $classes = $this->input->getArgument('classes') ?? $this->input->getOption('classes');

        $response = [];
        foreach (explode(',', $classes) as $class) {
            $class = trim($class);
            if (! str_contains($class, '\\')) {
                $class = 'Database\\Seeders\\'.$class;
            }

            if ($class === 'Database\\Seeders\\DatabaseSeeder' &&
                ! class_exists($class)) {
                $class = 'DatabaseSeeder';
            }

            if (! class_exists($class)) {
                $this->components->error("Seeder class [{$class}] does not exist.");
                continue;
            }
            if (! is_subclass_of($class, 'Illuminate\Database\Seeder')) {
                $this->components->error("Seeder class [{$class}] must extend Illuminate\\Database\\Seeder.");
                continue;
            }
            if (! method_exists($class, 'run')) {
                $this->components->error("Seeder class [{$class}] must define a run method.");
                continue;
            }
            $this->components->info("Seeding: {$class}");

            $response[] = $this->laravel->make($class)
                ->setContainer($this->laravel)
                ->setCommand($this);
        }

        if (count($response) === 1) {
            return $response[0];
        }
        return function () use ($response) {
            foreach ($response as $seeder) {
                $seeder->__invoke();
            }
        };
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
            ['classes', InputArgument::OPTIONAL, 'The class name of the root seeder. (Use Comma seperator for multiple classes)', null],
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
            ['classes', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder. (Use Comma seperator for multiple classes)', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
