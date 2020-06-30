<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

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
     */
    protected function userProviderModel()
    {
        $config = $this->laravel['config'];

        $guard = $this->option('guard') ?: $config->get('auth.defaults.guard');

        return $config->get(
            'auth.providers.'.$config->get('auth.guards.'.$guard.'.provider').'.model'
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

        if (Str::startsWith($model, '\\')) {
            $namespacedModel = trim($model, '\\');
        } else {
            $namespacedModel = $this->qualifyModel($model);
        }

        $model = class_basename(trim($model, '\\'));

        $dummyUser = class_basename($this->userProviderModel());

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
        ];

        $stub = str_replace(
            array_keys($replace), array_values($replace), $stub
        );

        return str_replace(
            "use {$namespacedModel};\nuse {$namespacedModel};", "use {$namespacedModel};", $stub
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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to'],
            ['guard', 'g', InputOption::VALUE_OPTIONAL, 'The guard that the policy relies on'],
        ];
    }
}
