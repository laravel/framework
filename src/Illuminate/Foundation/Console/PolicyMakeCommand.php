<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:policy')]
class PolicyMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Policy';

    /**
     * The models that should have policies generated.
     *
     * @var array
     */
    protected $models = [];

    /**
     * Indicates whether to generate policies for all models.
     *
     * @var bool
     */
    protected $allModels = false;

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->replaceUserNamespace(
            parent::buildClass($name)
        );

        $model = $this->option('model');

        return $model ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Replace the User model namespace.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceUserNamespace($stub)
    {
        $model = $this->userProviderModel();

        if (! $model) {
            return $stub;
        }

        return str_replace(
            $this->rootNamespace().'User',
            $model,
            $stub
        );
    }

    /**
     * Get the model for the guard's user provider.
     *
     * @return string|null
     *
     * @throws \LogicException
     */
    protected function userProviderModel()
    {
        $config = $this->laravel['config'];

        $guard = $this->option('guard') ?: $config->get('auth.defaults.guard');

        if (is_null($guardProvider = $config->get('auth.guards.'.$guard.'.provider'))) {
            throw new LogicException('The ['.$guard.'] guard is not defined in your "auth" configuration file.');
        }

        if (! $config->get('auth.providers.'.$guardProvider.'.model')) {
            return 'App\\Models\\User';
        }

        return $config->get(
            'auth.providers.'.$guardProvider.'.model'
        );
    }

    /**
     * Replace the model for the given stub.
     *
     * @param  string  $stub
     * @param  string  $model
     * @return string
     */
    protected function replaceModel($stub, $model)
    {
        $model = str_replace('/', '\\', $model);

        if (str_starts_with($model, '\\')) {
            $namespacedModel = trim($model, '\\');
        } else {
            $namespacedModel = $this->qualifyModel($model);
        }

        $model = class_basename(trim($model, '\\'));

        $userModel = $this->userProviderModel();
        $dummyUser = class_basename($userModel);

        $dummyModel = Str::camel($model) === 'user' ? 'model' : $model;

        $replace = [
            'NamespacedDummyModel' => $namespacedModel,
            '{{ namespacedModel }}' => $namespacedModel,
            '{{namespacedModel}}' => $namespacedModel,
            'DummyModel' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            'dummyModel' => Str::camel($dummyModel),
            '{{ modelVariable }}' => Str::camel($dummyModel),
            '{{modelVariable}}' => Str::camel($dummyModel),
            'DummyUser' => $dummyUser,
            '{{ user }}' => $dummyUser,
            '{{user}}' => $dummyUser,
            '$user' => '$'.Str::camel($dummyUser),
            '{{ namespacedUserModel }}' => $userModel,
        ];

        $stub = str_replace(
            array_keys($replace), array_values($replace), $stub
        );

        return preg_replace(
            vsprintf('/use %s;[\r\n]+use %s;/', [
                preg_quote($namespacedModel, '/'),
                preg_quote($namespacedModel, '/'),
            ]),
            "use {$namespacedModel};",
            $stub
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('model')
            ? $this->resolveStubPath('/stubs/policy.stub')
            : $this->resolveStubPath('/stubs/policy.plain.stub');
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
        return $rootNamespace.'\Policies';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Generate policies for all existing models'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the policy already exists'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to'],
            ['guard', 'g', InputOption::VALUE_OPTIONAL, 'The guard that the policy relies on'],
        ];
    }

    protected function getArguments()
    {
        return [
            ['name', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'The name of the policy'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->allModels = $this->option('all');

        if (! $this->argument('name')) {
            if ($this->allModels) {
                $this->input->setArgument('name', 'DummyPolicyName');
            } else {
                $name = text(
                    label: 'What should the policy be named?',
                    placeholder: 'E.g. PostPolicy',
                    required: true,
                    validate: fn ($value) => match (true) {
                        empty($value) => 'The policy name is required.',
                        $this->isReservedName($value) => 'The name "'.$value.'" is reserved by PHP.',
                        default => null,
                    }
                );
                $this->input->setArgument('name', $name);
            }
        }

        if ($this->allModels) {
            return $this->handleAllModels();
        }

        return parent::handle();
    }

    /**
     * Handle generating policies for all models.
     *
     * @return int
     */
    protected function handleAllModels()
    {
        if (! $this->argument('name')) {
            $this->input->setArgument('name', 'DummyPolicyName');
        }

        $this->models = $this->getAllModels();

        if (empty($this->models)) {
            $this->components->error('No models found to generate policies for.');

            return 1;
        }

        $successCount = 0;

        foreach ($this->models as $model) {
            if ($this->generatePolicyForModel($model)) {
                $successCount++;
            }
        }

        $this->components->info("Generated policies for {$successCount} models.");

        return 0;
    }

    /**
     * Generate a policy for the given model.
     *
     * @param  string  $model
     * @return bool
     */
    protected function generatePolicyForModel($model)
    {
        $name = class_basename($model).'Policy';
        $this->input->setArgument('name', $name);
        $this->input->setOption('model', $model);

        $qualifiedName = $this->qualifyClass($name);

        if ($this->isReservedName($name)) {
            $this->components->error('The name "'.$name.'" is reserved by PHP.');

            return false;
        }

        $path = $this->getPath($qualifiedName);

        if ((! $this->hasOption('force') || ! $this->option('force')) && $this->alreadyExists($name)) {
            $this->components->warn("Skipped {$name} (already exists).");

            return false;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($qualifiedName)));

        $this->components->info($this->type.' '.$name.' created successfully.');

        return true;
    }

    /**
     * Get all the model classes in the application.
     *
     * @return array
     */
    protected function getAllModels()
    {
        $models = [];
        $modelPath = app_path('Models');

        if (! is_dir($modelPath)) {
            return $models;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modelPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $modelClasses = $this->getModelsFromFile($file);
                $models = array_merge($models, $modelClasses);
            }
        }

        return array_unique($models);
    }

    /**
     * Get model classes from a file using token parsing.
     *
     * @param  \SplFileInfo  $file
     * @return array
     */
    protected function getModelsFromFile($file)
    {
        $path = $file->getRealPath();
        $content = file_get_contents($path);

        if (! preg_match('/extends\s+Model|extends\s+Authenticatable|Illuminate\\\\Database\\\\Eloquent/', $content)) {
            return [];
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $classes = [];
        $i = 0;

        while (isset($tokens[$i])) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = $this->extractNamespace($tokens, $i);
            }

            if ($tokens[$i][0] === T_CLASS && $this->isClassDeclaration($tokens, $i)) {
                $className = $this->extractClassName($tokens, $i);
                if ($className && $this->isModelClass($content)) {
                    $fullClassName = $namespace ? $namespace.'\\'.$className : $className;
                    $classes[] = $fullClassName;
                }
            }

            $i++;
        }

        return $classes;
    }

    /**
     * Extract namespace from tokens.
     *
     * @param  array  $tokens
     * @param  int  &$i
     * @return string
     */
    protected function extractNamespace($tokens, &$i)
    {
        $namespace = '';
        $i++;

        while (isset($tokens[$i]) && $tokens[$i] !== ';' && $tokens[$i] !== '{') {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
                $namespace .= $tokens[$i][1];
            }
            $i++;
        }

        return trim($namespace);
    }

    /**
     * Check if this is a class declaration (not a class reference).
     *
     * @param  array  $tokens
     * @param  int  $i
     * @return bool
     */
    protected function isClassDeclaration($tokens, $i)
    {
        return $i === 0 || $tokens[$i - 1][0] !== T_DOUBLE_COLON;
    }

    /**
     * Extract class name from tokens.
     *
     * @param  array  $tokens
     * @param  int  $i
     * @return string|null
     */
    protected function extractClassName($tokens, $i)
    {
        for ($j = $i + 1; isset($tokens[$j]); $j++) {
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                return $tokens[$j][1];
            }

            if (! is_array($tokens[$j]) || ! in_array($tokens[$j][0], [T_WHITESPACE])) {
                break;
            }
        }

        return null;
    }

    /**
     * Check if a class is a model by examining the file content.
     *
     * @param  string  $content
     * @return bool
     */
    protected function isModelClass($content)
    {
        $patterns = [
            '/extends\s+Model/',
            '/extends\s+Authenticatable/',
            '/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Model/',
            '/use\s+Illuminate\\\\Foundation\\\\Auth\\\\User\s+as\s+Authenticatable/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $choices = array_merge(['All Models'], $this->possibleModels());

        $model = suggest(
            label: 'What model should this policy apply to?',
            options: $choices,
            placeholder: 'Start typing to search...',
            required: false,
            hint: 'Leave empty to create a plain policy or select "All Models" to generate for all models',
            scroll: min(10, count($choices)),
            validate: function ($value) use ($choices) {
                if ($value && ! in_array($value, $choices)) {
                    return 'The selected model is not valid.';
                }

                return null;
            }
        );

        if ($model === 'All Models') {
            $input->setOption('all', true);
            $input->setArgument('name', 'DummyPolicyName');
        } elseif ($model) {
            $input->setOption('model', $model);

            if (! $this->argument('name')) {
                $input->setArgument('name', class_basename($model).'Policy');
            }
        }
    }
}
