<?php

namespace Illuminate\Foundation\Console;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'make:model')]
class ModelMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:model';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'make:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    public function __construct(
        Filesystem $files,
        private Connection $databaseConnection,
        private Composer $composer
    ) {
        parent::__construct($files);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }
    }

    /**
     * Create a model factory for the model.
     *
     * @return void
     */
    protected function createFactory()
    {
        $factory = Str::studly($this->argument('name'));

        $this->call('make:factory', [
            'name' => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = $this->getTableName();

        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    protected function getTableName()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        return $table;
    }

    /**
     * Create a seeder file for the model.
     *
     * @return void
     */
    protected function createSeeder()
    {
        $seeder = Str::studly(class_basename($this->argument('name')));

        $this->call('make:seeder', [
            'name' => "{$seeder}Seeder",
        ]);
    }

    /**
     * Create a controller for the model.
     *
     * @return void
     */
    protected function createController()
    {
        $controller = Str::studly(class_basename($this->argument('name')));

        $modelName = $this->qualifyClass($this->getNameInput());

        $this->call('make:controller', array_filter([
            'name' => "{$controller}Controller",
            '--model' => $this->option('resource') || $this->option('api') ? $modelName : null,
            '--api' => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
        ]));
    }

    /**
     * Create a policy file for the model.
     *
     * @return void
     */
    protected function createPolicy()
    {
        $policy = Str::studly(class_basename($this->argument('name')));

        $this->call('make:policy', [
            'name' => "{$policy}Policy",
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('pivot')) {
            return $this->resolveStubPath('/stubs/model.pivot.stub');
        }

        if ($this->option('morph-pivot')) {
            return $this->resolveStubPath('/stubs/model.morph-pivot.stub');
        }

        return $this->resolveStubPath('/stubs/model.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return is_dir(app_path('Models')) ? $rootNamespace.'\\Models' : $rootNamespace;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, policy, and resource controller for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['morph-pivot', null, InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom polymorphic intermediate table model'],
            ['policy', null, InputOption::VALUE_NONE, 'Create a new policy for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model'],
            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller'],
            ['api', null, InputOption::VALUE_NONE, 'Indicates if the generated controller should be an API controller'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Create new form request classes and use them in the resource controller'],
            ['phpdoc', null, InputOption::VALUE_NONE, 'Indicates if the generated model should have phpDoc for fields'],
        ];
    }

    public function buildClass($name)
    {
        $builtClass = parent::buildClass($name);
        $builtClassWithoutPhpDoc = str_replace("\n{{ phpDoc }}", '', $builtClass);

        if (! $this->option('phpdoc')) {
            return $builtClassWithoutPhpDoc;
        }

        if (! interface_exists('Doctrine\DBAL\Driver')) {
            if (! $this->components->confirm('Create model with phpDoc properties requires requires the Doctrine DBAL (doctrine/dbal) package. Would you like to install it?')) {
                return 1;
            }

            return $this->installDependencies();
        }

        $schema = $this->databaseConnection->getDoctrineSchemaManager();
        $table = $this->getTableName();

        if (! $schema->tablesExist($table)) {
            return $builtClassWithoutPhpDoc;
        }

        $columns = $schema->listTableColumns($table);
        $phpDoc = $this->buildPhpDocForColumns($columns);

        return str_replace('{{ phpDoc }}', $phpDoc, $builtClass);
    }

    /**
     * @param  Column[]  $columns
     * @return string
     */
    public function buildPhpDocForColumns($columns): string
    {
        $phpDocStr = '/**';

        foreach ($columns as $column) {
            $phpDocStr .= sprintf(
                '%s* @property %s %s',
                "\n",
                $this->resolveColumnType($column),
                '$'.$column->getName()
            );
        }

        return $phpDocStr."\n*/";
    }

    /**
     * Resolve from DB type to PHP type.
     *
     * @param  Column  $columm
     * @return string
     */
    protected function resolveColumnType($columm): string
    {
        $phpType = match ($columm->getType()->getName()) {
            Types::BIGINT, Types::INTEGER, Types::SMALLINT => 'int',
            Types::STRING, Types::TEXT, Types::BINARY, Types::GUID => 'string',
            Types::ARRAY, Types::JSON, Types::SIMPLE_ARRAY => 'array',
            Types::FLOAT, Types::DECIMAL => 'float',
            Types::OBJECT => 'object',
            Types::BOOLEAN => 'bool',

            Types::DATE_MUTABLE,
            Types::TIME_MUTABLE,
            Types::DATETIME_MUTABLE,
            Types::DATETIMETZ_MUTABLE => '\Carbon\Carbon|string',

            default => 'mixed',
        };

        if (! $columm->getNotnull() && $phpType !== 'mixed') {
            $phpType .= '|null';
        }

        return $phpType;
    }

    /**
     * Install the command's dependencies.
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\ProcessSignaledException
     */
    protected function installDependencies()
    {
        $command = collect($this->composer->findComposer())
            ->push('require doctrine/dbal')
            ->implode(' ');

        $process = Process::fromShellCommandline($command, null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->components->warn($e->getMessage());
            }
        }

        try {
            $process->run(fn ($type, $line) => $this->output->write($line));
        } catch (ProcessSignaledException $e) {
            if (extension_loaded('pcntl') && $e->getSignal() !== SIGINT) {
                throw $e;
            }
        }
    }
}
