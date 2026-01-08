<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            if (! $this->alreadyExists($this->getNameInput())) {
                return false;
            }

            if (! confirm('Do you want to generate additional components for the model?')) {
                return false;
            } else {
                $this->afterPromptingForMissingArguments($this->input, $this->output);
            }
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
        } elseif ($this->option('requests')) {
            $this->createFormRequests();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }
    }

    /**
     * Get the table name.
     */
    protected function getTableName()
    {
        if (! blank($this->option('table'))) {
            return (string) $this->option('table');
        }

        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            return Str::singular($table);
        }

        return $table;
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
            '--test' => $this->option('test'),
            '--pest' => $this->option('pest'),
        ]));
    }

    /**
     * Create the form requests for the model.
     *
     * @return void
     */
    protected function createFormRequests()
    {
        $request = Str::studly(class_basename($this->argument('name')));

        $this->call('make:request', [
            'name' => "Store{$request}Request",
        ]);

        $this->call('make:request', [
            'name' => "Update{$request}Request",
        ]);
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
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $replace = $this->buildFactoryReplacements();

        $stub = parent::buildClass($name);

        $this->replaceTable($stub);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );
    }

    /**
     * Build the replacements for a factory.
     *
     * @return array<string, string>
     */
    protected function buildFactoryReplacements()
    {
        $replacements = [];

        if ($this->option('factory') || $this->option('all')) {
            $modelPath = Str::of($this->argument('name'))->studly()->replace('/', '\\')->toString();

            $factoryNamespace = '\\Database\\Factories\\'.$modelPath.'Factory';

            $factoryCode = <<<EOT
            /** @use HasFactory<$factoryNamespace> */
                use HasFactory;
            EOT;

            $replacements['{{ factory }}'] = $factoryCode;
            $replacements['{{ factoryImport }}'] = 'use Illuminate\Database\Eloquent\Factories\HasFactory;';
        } else {
            $replacements['{{ factory }}'] = '//';
            $replacements["{{ factoryImport }}\n"] = '';
            $replacements["{{ factoryImport }}\r\n"] = '';
        }

        return $replacements;
    }

    /**
     * Replace the table placeholder for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceTable(&$stub): static
    {
        $replace = $this->buildTableReplacements();

        $stub = str_replace(
            array_keys($replace),
            array_values($replace),
            $stub
        );

        return $this;
    }

    /**
     * Build the replacements for the {{ table }} placeholder.
     *
     * Rules:
     * - If --table is provided: inject protected $table = '...';
     * - If pivot/morph-pivot and no --table: replace with // (keep class body non-empty)
     * - If normal model and no --table: remove placeholder incl. newline/spacing
     *
     * @return array<string, string>
     */
    protected function buildTableReplacements(): array
    {
        if (! blank($this->option('table'))) {
            $table = $this->getTableName();

            $tableCode = <<<EOT
                /**
                 * The table associated with the model.
                 *
                 * @var string
                 */
                protected \$table = '{$table}';
            EOT;

            return [
                '{{ table }}' => $tableCode,
            ];
        }

        if ($this->option('pivot') || $this->option('morph-pivot')) {
            return [
                '{{ table }}' => '//',
            ];
        }

        return [
            "    {{ table }}\n" => '',
            "    {{ table }}\r\n" => '',
            "{{ table }}\n" => '',
            "{{ table }}\r\n" => '',
            '{{ table }}' => '',
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
            ['all', 'a', InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, policy, resource controller, and form request classes for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['morph-pivot', null, InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom polymorphic intermediate table model'],
            ['policy', null, InputOption::VALUE_NONE, 'Create a new policy for the model'],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model'],
            ['pivot', 'p', InputOption::VALUE_NONE, 'Indicates if the generated model should be a custom intermediate table model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller'],
            ['api', null, InputOption::VALUE_NONE, 'Indicates if the generated controller should be an API resource controller'],
            ['requests', 'R', InputOption::VALUE_NONE, 'Create new form request classes and use them in the resource controller'],
            ['table', 't', InputOption::VALUE_OPTIONAL, 'Override the table name for the generated model'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        (new Collection(multiselect('Would you like any of the following?', [
            'seed' => 'Database Seeder',
            'factory' => 'Factory',
            'requests' => 'Form Requests',
            'migration' => 'Migration',
            'policy' => 'Policy',
            'resource' => 'Resource Controller',
        ])))->each(fn ($option) => $input->setOption($option, true));
    }
}
