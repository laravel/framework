<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class ConfigMakeCommand extends GeneratorCommand
{
    /**
     * The type of class being generated.
     *
     * @var string
     *
     */
    protected $type = 'Config';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('stubs/config-make.stub');
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
}
