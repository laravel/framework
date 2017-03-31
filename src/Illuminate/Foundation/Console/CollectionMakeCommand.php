<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class CollectionMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent collection class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Collection';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/collection.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Collections';
    }
}
