<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:command')]
class ConsoleMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Artisan command';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Console command';

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $command = $this->option('command') ?: 'app:'.Str::of($name)->classBasename()->kebab()->value();

        return str_replace(['dummy:command', '{{ command }}'], $command, $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $this->resolveStubPath('/stubs/console.stub');
    }

    /**
     * Resolve the default fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveDefaultStubPath($stub)
    {
        return __DIR__.$stub;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->generatorPreset()->commandNamespace();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command'],
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the console command already exists'],
            ['command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that will be used to invoke the class'],
        ];
    }
}
