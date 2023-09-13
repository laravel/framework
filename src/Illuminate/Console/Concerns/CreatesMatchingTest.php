<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait CreatesMatchingTest
{
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
     * @return bool
     */
    protected function handleTestCreation($path)
    {
        if (! $this->option('test') && ! $this->option('pest')) {
            return false;
        }

        $sourcePath = in_array(CreatesUsingGeneratorPreset::class, class_uses_recursive($this))
            ? $this->generatorPreset()->sourcePath()
            : $this->laravel['path'];

        return $this->callSilent('make:test', [
            'name' => Str::of($path)->after($sourcePath)->beforeLast('.php')->append('Test')->replace('\\', '/'),
            '--pest' => $this->option('pest'),
        ]) == 0;
    }
}
