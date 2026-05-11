<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:facade')]
class FacadeMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
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
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->hasTargetClass()
            ? $this->resolveStubPath('/stubs/facade.targeted.stub')
            : $this->resolveStubPath('/stubs/facade.stub');
    }

    /**
     * Gets the facade accessor.
     *
     * @return string
     */
    protected function getAccessor(): string
    {
        return $this->option('accessor')
            ?: $this->getTargetClass()
            ?: Str::slug($this->getNameInput());
    }

    /**
     * Check if there is a class name target.
     *
     * @return bool
     */
    protected function hasTargetClass(): bool
    {
        return ($this->option('target') && class_exists($this->option('target')))
            || ($this->option('accessor') && class_exists($this->option('accessor')))
            || class_exists($this->getAccessor());
    }

    /**
     * Gets the target class if there is one.
     *
     * @return string
     */
    protected function getTargetClass(): string
    {
        $name = str_replace('/', '\\', ltrim($this->getNameInput(), '\\/'));
        $rootNamespace = trim($this->rootNamespace(), '\\');

        return $this->option('target')
            ?: array_first(array_filter([
                $rootNamespace.'\\Services\\'.$name.'Service',
                $rootNamespace.'\\Services\\'.$name,
                $rootNamespace.'\\'.$name.'Service',
            ], class_exists(...)))
            ?: (class_exists($this->option('accessor') ?? 'dummy class') ? $this->option('accessor') : '');
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

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Support\Facades';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $replace = $this->buildFacadeReplacements();

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the replacements for a factory.
     *
     * @return array<string, string>
     */
    protected function buildFacadeReplacements(): array
    {
        $accessor = $this->getAccessor();

        $replacements = [
            '{{ accessor }}' => class_exists($accessor) ? Str::wrap($accessor, '\\', '::class') : Str::wrap($accessor, '\''),
        ];

        if ($this->hasTargetClass()) {
            $target = $this->getTargetClass();
            $import = $target;
            $alias = class_basename($target);
            if ($alias === class_basename($this->getNameInput())) {
                $alias = $alias.'Service';
                $import .= ' as '.$alias;
            }

            $replacements['{{ accessor }}'] = is_a($accessor, $target, true) ? class_basename($alias).'::class' : $replacements['{{ accessor }}'];
            $replacements['{{ qualifiedTarget }}'] = $import;
            $replacements['{{ target }}'] = $alias;
        }

        return $replacements;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['target', 't', InputOption::VALUE_REQUIRED, 'Set the target service class'],
            ['accessor', 'a', InputOption::VALUE_REQUIRED, 'Set the facade accessor'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the facade even if the class already exists'],
        ];
    }
}
