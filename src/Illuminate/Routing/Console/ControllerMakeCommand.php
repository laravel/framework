<?php

namespace Illuminate\Routing\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ControllerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('model')) {
            return __DIR__.'/stubs/controller.model.stub';
        } elseif ($this->option('resource')) {
            return __DIR__.'/stubs/controller.stub';
        }

        return __DIR__.'/stubs/controller.plain.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller class.'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Use specified model.'],
        ];
    }

    /**
     * Parses the model namespace.
     *
     * @param  string  $modelNamespace
     * @return string
     */
    protected function parseModel($modelNamespace)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $modelNamespace)) {
            $this->line('');
            $this->error('                                          ');
            $this->error('  Model name contains invalid characters  ');
            $this->error('                                          ');
            exit(1);
        }

        if (Str::contains($modelNamespace, '/')) {
            $modelNamespace = str_replace('/', '\\', $modelNamespace);
        }

        $modelNamespace = trim($modelNamespace, '\\');
        $rootNamespace = $this->laravel->getNamespace();

        if (! Str::startsWith($modelNamespace, $rootNamespace)) {
            $modelNamespace = $rootNamespace.$modelNamespace;
        }

        return $modelNamespace;
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];
        if ($this->option('model')) {
            $modelNamespace = $this->parseModel($this->option('model'));
            $modelClass = last(explode('\\', $modelNamespace));
            $replace = [
                'DummyModelNamespace' => $modelNamespace,
                'DummyModelClass' => $modelClass,
                'DummyModelVariable' => lcfirst($modelClass),
            ];
        }

        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));
    }
}
