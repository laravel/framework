<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
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
        $stub = parent::buildClass($name);

        $model = $this->option('model');

        return $model ? $this->replaceModel($stub, $model) : $stub;
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
        list($model, $namespace) = $this->getModelAndNamespace($model);

        $stub = str_replace('DummyModelNamespace', $namespace, $stub);

        $stub = str_replace('DummyModel', $model, $stub);

        $stub = str_replace('dummyModelName', Str::camel($model), $stub);

        return str_replace('dummyPluralModelName', Str::plural(Str::camel($model)), $stub);
    }

    /**
     * Separate the namespaced model into
     * the namespace and the model name.
     *
     * @param  string  $model
     * @return array
     */
    protected function getModelAndNamespace($model)
    {
        $model = str_replace('/', '\\', $model);

        $modelName = class_basename(trim($model, '\\'));

        $namespace = substr($model, 0, -strlen($modelName));

        $namespace = ltrim($namespace, '\\');

        $namespace = $namespace ?: $this->laravel->getNamespace();

        return [$modelName, $namespace];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('model')) {
            return __DIR__.'/stubs/policy.stub';
        }

        return __DIR__.'/stubs/policy.plain.stub';
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
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to.'],
        ];
    }
}
