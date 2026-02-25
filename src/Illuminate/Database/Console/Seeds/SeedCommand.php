<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\multisearch;

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
        if ($this->option('list')) {
            return $this->listSeeders();
        }

        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $seeders = $this->option('select')
            ? $this->selectSeeders()
            : [$this->getSeeder()];

        if (empty($seeders)) {
            return Command::SUCCESS;
        }

        $this->components->info('Seeding database.');

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        Model::unguarded(function () use ($seeders) {
            foreach ($seeders as $seeder) {
                $seeder->__invoke();
            }
        });

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        return Command::SUCCESS;
    }

    /**
     * List all available seeders.
     *
     * @return int
     */
    protected function listSeeders()
    {
        $seeders = $this->getAvailableSeeders();

        if ($seeders->isEmpty()) {
            $this->components->info('No seeders found.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $seeders->each(fn ($class, $name) => $this->components->twoColumnDetail($name, $class));
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Interactively select seeders to run.
     *
     * @return array<\Illuminate\Database\Seeder>
     */
    protected function selectSeeders()
    {
        $seeders = $this->getAvailableSeeders();

        if ($seeders->isEmpty()) {
            $this->components->info('No seeders found.');

            return [];
        }

        $selected = multisearch(
            label: 'Which seeders would you like to run?',
            options: fn (string $search) => $seeders
                ->filter(fn ($class, $name) => str_contains(strtolower($name), strtolower($search)))
                ->keys()
                ->values()
                ->all(),
            placeholder: 'Search seeders...',
        );

        if (empty($selected)) {
            return [];
        }

        return array_map(
            fn ($name) => $this->laravel->make($seeders[$name])
                ->setContainer($this->laravel)
                ->setCommand($this),
            $selected
        );
    }

    /**
     * Get all available seeder classes from the seeders directory.
     *
     * @return \Illuminate\Support\Collection<string, string>
     */
    protected function getAvailableSeeders()
    {
        $seedersPath = $this->laravel->databasePath('seeders');

        if (! is_dir($seedersPath)) {
            return new Collection;
        }

        return (new Collection(
            Finder::create()->files()->name('*.php')->in($seedersPath)
        ))
            ->map(function ($file) use ($seedersPath) {
                $name = str_replace([DIRECTORY_SEPARATOR, '.php'], ['/', ''],
                    Str::after($file->getRealPath(), realpath($seedersPath).DIRECTORY_SEPARATOR)
                );
                $class = 'Database\\Seeders\\'.str_replace('/', '\\', $name);

                return compact('name', 'class');
            })
            ->filter(fn ($item) => class_exists($item['class'])
                && is_subclass_of($item['class'], Seeder::class)
                && ! (new \ReflectionClass($item['class']))->isAbstract())
            ->sortBy('name')
            ->values()
            ->mapWithKeys(fn ($item) => [$item['name'] => $item['class']]);
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
            ['list', null, InputOption::VALUE_NONE, 'List available seeders without running them'],
            ['select', null, InputOption::VALUE_NONE, 'Interactively select seeders to run'],
        ];
    }
}
