<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Support\Hooks\Hook;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait CreatesMatchingTest
{
    /**
     * Register "initialize" hook.
     *
     * @return \Illuminate\Support\Hooks\Hook
     */
    public function registerCreatesMatchingTestInitializeHook(): Hook
    {
        return Hook::make('initialize', fn () => $this->addTestOptions());
    }

    /**
     * Register "generate" hook.
     *
     * @return \Illuminate\Support\Hooks\Hook
     */
    public function registerCreatesMatchingTestGenerateHook(): Hook
    {
        return Hook::make('generate', function ($name, $path) {
            // We want to run test creation after generation, so we'll return a callback to execute at the end
            return fn () => $this->handleTestCreation($path);
        });
    }

    /**
     * Add the standard command options for generating matching tests.
     *
     * @return void
     */
    protected function addTestOptions()
    {
        foreach (['test' => 'PHPUnit', 'pest' => 'Pest'] as $option => $name) {
            $this->getDefinition()->addOption(new InputOption(
                $option,
                null,
                InputOption::VALUE_NONE,
                "Generate an accompanying {$name} test for the {$this->type}"
            ));
        }
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     * @return void
     */
    protected function handleTestCreation($path)
    {
        if (! $this->option('test') && ! $this->option('pest')) {
            return;
        }

        $this->call('make:test', [
            'name'   => Str::of($path)
                ->after($this->laravel['path'])
                ->beforeLast('.php')
                ->append('Test')
                ->replace('\\', '/'),
            '--pest' => $this->option('pest'),
        ]);
    }
}
