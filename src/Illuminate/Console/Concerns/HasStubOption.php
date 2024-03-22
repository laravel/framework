<?php

namespace Illuminate\Console\Concerns;

use function Illuminate\Filesystem\join_paths;

trait HasStubOption
{
    /**
     * Get the console command arguments.
     */
    abstract protected function getOptions(): array;

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    #[\Override]
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStubOption() ?? $this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Get a stub file for the generator from a stub option.
     *
     * @return string|null
     */
    protected function getStubOption()
    {
        if (! $this->hasOption('stub') || ! $this->option('stub')) {
            return null;
        }

        $stub = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($this->option('stub')));

        return match (true) {
            file_exists($namedStub = $this->laravel->basePath(join_paths('stubs', $stub.'.stub'))) => $namedStub,
            file_exists($stubPath = $stub) => $stubPath,
            default => null,
        };
    }
}
