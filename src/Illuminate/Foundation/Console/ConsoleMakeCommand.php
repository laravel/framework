<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:command')]
class ConsoleMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:command
                    {name : The name of the command}
                    {--f|force : Create the class even if the console command already exists}
                    {--command= : The terminal command that will be used to invoke the class}';

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

        $command = $this->option('command') ?: 'app:'.(new Stringable($name))->classBasename()->kebab()->value();

        return str_replace(['dummy:command', '{{ command }}'], $command, $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/console.stub';

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
        return $rootNamespace.'\Console\Commands';
    }
}
