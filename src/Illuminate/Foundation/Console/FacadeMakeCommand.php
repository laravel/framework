<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Illuminate\Filesystem\join_paths;

#[AsCommand(name: 'make:facade', aliases: ['facade:make'])]
class FacadeMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:facade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new facade';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Facade';

    /**
     * The console command name aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['facade:make'];


    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Facades';
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        $relativePath = join_paths('stubs', 'facade.stub');

        return file_exists($customPath = $this->laravel->basePath($relativePath))
            ? $customPath
            : join_paths(__DIR__, $relativePath);
    }

    /**
     * Get the console command arguments.
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the facade already exists'],
        ];
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => 'What should the facade be named?',
        ];
    }
}
