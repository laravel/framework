<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:collection')]
class CollectionMakeCommand extends GeneratorCommand
{
    protected $name = 'make:collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model Collection class';

    protected $type = 'Eloquent Collection';

    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/collection.stub');
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

    protected function buildClass($name)
    {
        $collection = class_basename(Str::ucfirst(str_replace('Collection', '', $name)));

        $namespace = $this->getNamespace(
            Str::replaceFirst($this->rootNamespace(), 'App\\', $this->qualifyClass($this->getNameInput()))
        );

        $replace = [
            '{{ collectionNamespace }}' => $namespace,
            '{{ collection }}' => $collection,
            '{{collection}}' => $collection,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Collections';
    }

    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The name of the model'],
        ];
    }
}
