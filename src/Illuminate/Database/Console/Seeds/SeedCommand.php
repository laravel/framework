<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
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
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->input->getArgument('class') ?? $this->input->getOption('class');

        if (! str_contains($class, '\\')) {
            $class = $this->resolveSeederClass($class);
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
     * Resolve the seeder class from the registered seeder paths.
     *
     * @param  string  $class
     * @return string
     */
    protected function resolveSeederClass($class)
    {
        foreach ($this->getSeederPaths() as $path) {
            $file = $path.DIRECTORY_SEPARATOR.$class.'.php';

            if (! is_file($file)) {
                continue;
            }

            if (preg_match('/^namespace\s+([^;]+);/m', file_get_contents($file), $matches) &&
                class_exists($namespaced = trim($matches[1]).'\\'.$class)) {
                return $namespaced;
            }
        }

        return 'Database\\Seeders\\'.$class;
    }

    /**
     * Get all of the seeder paths.
     *
     * @return string[]
     */
    protected function getSeederPaths()
    {
        return array_merge(
            Seeder::paths(), [$this->getSeederPath()]
        );
    }

    /**
     * Get the path to the seeder directory.
     *
     * @return string
     */
    protected function getSeederPath()
    {
        return $this->laravel->databasePath().DIRECTORY_SEPARATOR.'seeders';
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
        ];
    }
}
