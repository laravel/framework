<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function json_encode;

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

        Model::unguarded($this->seed(...));

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        return 0;
    }

    /**
     * Invoke the database seeder.
     *
     * @return void
     */
    protected function seed()
    {
        $seeder = $this->getSeeder();

        $continueFilePath = $this->continueFilePath($seeder);

        if ($this->option('continue')) {
            if ($list = $this->retrieveContinueData($continueFilePath)) {
                $this->components->info('Resuming from previous seed operation.');
                Seeder::setContinue($list);
            } else {
                $this->components->warn('No previous seed operation found.');
            }
        }

        if (! $seeder->useTransactions()) {
            $this->components->warn('Transactions are disabled. Errors may yield incomplete records.');
        }

        try {
            $seeder->__invoke();
        } catch (Throwable $e) {
            $this->storeContinueData($continueFilePath, Seeder::getContinue());

            throw $e;
        }

        $this->deleteContinueData($continueFilePath);
    }

    /**
     * Retrieve the "continue" data list from a JSON file as an array.
     *
     * @param  string  $path
     * @return array
     */
    protected function retrieveContinueData($path)
    {
        if ($this->getLaravel()->bound(Filesystem::class)) {
            $files = $this->getLaravel()->make(Filesystem::class);

            return $files->exists($path) ? $files->json($path) : [];
        }

        throw new RuntimeException('Unable to retrieve continue data. Please install the "illuminate/filesystem" package to use the --continue option.');
    }

    /**
     * Store the "continue" data list to a JSON file.
     *
     * @param  string  $path
     * @param  array  $data
     * @return void
     */
    protected function storeContinueData($path, array $data)
    {
        if ($this->getLaravel()->bound(Filesystem::class)) {
            $files = $this->getLaravel()->make(Filesystem::class);

            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, json_encode($data));
        }
    }

    /**
     * Returns the "continue" file path for the invoked seeder.
     *
     * @param  \Illuminate\Database\Seeder  $seeder
     * @return string
     */
    protected function continueFilePath($seeder)
    {
        return $this->getLaravel()->storagePath(
            'framework/database/seeder.'.str_replace('\\', '_', get_class($seeder)).'.json'
        );
    }

    /**
     * Delete the "continue" file from the filesystem.
     *
     * @param  string  $path
     * @return void
     */
    protected function deleteContinueData($path)
    {
        if ($this->getLaravel()->bound(Filesystem::class)) {
            $this->getLaravel()->make(Filesystem::class)->delete($path);
        }
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
            ['continue', null, InputOption::VALUE_OPTIONAL, 'Continue from a previous seed operation'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
