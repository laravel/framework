<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;

class TestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:test {name : The name of the class} {--unit : Create a unit test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Test';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('unit')) {
            return __DIR__.'/stubs/unit-test.stub';
        } else {
            return __DIR__.'/stubs/test.stub';
        }
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        $name = Str::finish($name, 'Test');

        return $this->laravel->basePath().'/tests'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $class = Str::finish($class, 'Test');

        return str_replace('DummyClass', $class, $stub);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        if ($this->option('unit')) {
            return $rootNamespace.'\Unit';
        } else {
            return $rootNamespace.'\Feature';
        }
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return 'Tests';
    }
}
