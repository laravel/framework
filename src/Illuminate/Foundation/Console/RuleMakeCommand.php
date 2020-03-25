<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class RuleMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:rule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new validation rule';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Rule';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/rule.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Rules';
    }
}
