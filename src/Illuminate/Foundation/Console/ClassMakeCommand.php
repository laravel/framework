<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:class')]
class ClassMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:class';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Class';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('invokable')
            ? $this->resolveStubPath('/stubs/class.invokable.stub')
            : $this->resolveStubPath('/stubs/class.stub');
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
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function buildClass($name)
    {
        $stub = parent::buildClass($name);

        return $this->replaceStrict($stub, (bool) $this->option('strict'));
    }

    /**
     * @param  string  $stub
     * @param  bool  $isStrict
     * @return string
     */
    protected function replaceStrict($stub, bool $isStrict)
    {
        $replace = $isStrict ? 'declare(strict_types=1);' : '';

        $stub = str_replace(['{{ strict_types }}', '{{strict_types}}'], $replace, $stub);

        if ($isStrict === false) {
            $stub = str_replace("\n\n\n", "\n", $stub);
        }

        return $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate a single method, invokable class'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the class already exists'],
            ['strict', 's', InputOption::VALUE_NONE, 'Add strict mode to the class'],
        ];
    }
}
