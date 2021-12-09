<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Support\Hooks\Hook;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait CreatesMatchingTest
{
    /**
     * Hook to add the standard command options for generating matching tests.
     *
     * @return void
     */
    public function addTestOptionsHook(): Hook
    {
        return Hook::make('initialize', function () {
            foreach (['test' => 'PHPUnit', 'pest' => 'Pest'] as $option => $name) {
                $this->getDefinition()->addOption(new InputOption(
                    $option,
                    null,
                    InputOption::VALUE_NONE,
                    "Generate an accompanying {$name} test for the {$this->type}"
                ));
            }
        });
    }

    /**
     * Hook tocreate the matching test case if requested.
     *
     * @param  string  $path
     * @return void
     */
    public function addTestCreationHook($path): Hook
    {
        return Hook::make('generate', function () use ($path) {
            if (! $this->option('test') && ! $this->option('pest')) {
                return;
            }

            // Run make:test after the "generate" hook has finished
            return function () use ($path) {
                $this->call('make:test', [
                    'name'   => Str::of($path)
                        ->after($this->laravel['path'])
                        ->beforeLast('.php')
                        ->append('Test')
                        ->replace('\\', '/'),
                    '--pest' => $this->option('pest'),
                ]);
            };
        });
    }
}
