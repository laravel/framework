<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class ViewComposerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:composer {name : The name of the class} {--provider : Create the service provider if not already done}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view composer class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'View composer';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/view-composer.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\ViewComposers';
    }
}
