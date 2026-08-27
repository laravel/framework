<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'db:seed')]
class SeedCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed
                    {class? : The class name of the root seeder}
                    {--class=Database\\Seeders\\DatabaseSeeder : The class name of the root seeder}
                    {--database= : The database connection to seed}
                    {--force : Force the operation to run when in production}';

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
        if ($this->isProhibited() || ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $this->components->info('Seeding database.');

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        $seeder = $this->getSeeder();

        $requestedClass = $this->input->getArgument('class') ?? $this->input->getOption('class');

        $shouldReportProgress = ! in_array($requestedClass, [
            'Database\\Seeders\\DatabaseSeeder', 'DatabaseSeeder',
        ]);

        if ($shouldReportProgress) {
            $this->components->twoColumnDetail(
                get_class($seeder), '<fg=yellow;options=bold>RUNNING</>'
            );
        }

        $startTime = microtime(true);

        try {
            Model::unguarded(function () use ($seeder) {
                $seeder->__invoke();
            });
        } finally {
            if ($previousConnection) {
                $this->resolver->setDefaultConnection($previousConnection);
            }
        }

        if ($shouldReportProgress) {
            $runTime = number_format((microtime(true) - $startTime) * 1000);

            $this->components->twoColumnDetail(
                get_class($seeder), "<fg=gray>$runTime ms</> <fg=green;options=bold>DONE</>"
            );

            $this->newLine();
        }

        return self::SUCCESS;
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
}
