<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class HandlerCommandCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'handler:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new command handler class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Handler';

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $stub = str_replace(
            'DummyCommand', class_basename($this->option('command')), $stub
        );

        $stub = str_replace(
            'DummyFullCommand', $this->option('command'), $stub
        );

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/command-handler.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Handlers\Commands';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['command', null, InputOption::VALUE_REQUIRED, 'The command class the handler handles.'],
        ];
    }
}
