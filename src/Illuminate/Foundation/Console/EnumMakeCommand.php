<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:enum')]
class EnumMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new enum';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:enum';

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'Enum';

    /**
     * Get the desired view name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        $name = trim($this->argument('name'));

        $name = str_replace(['\\', '.'], '/', $this->argument('name'));

        return $name;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath(
            '/stubs/enum.stub',
        );
    }


    /**
     * Get the default namespace for the generator.
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Enums';
    }

    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        return str_replace('{{enum_name}}', $class, $stub);
    }
}
