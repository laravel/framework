<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Console\Generators\PresetManager;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait CreatesUsingGeneratorPreset
{
    /**
     * Add the standard command options for generating with preset.
     *
     * @return void
     */
    protected function addGeneratorPresetOptions()
    {
        $this->getDefinition()->addOption(new InputOption(
            'preset',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf('Preset used when generating %s', Str::lower($this->type)),
            'laravel',
        ));
    }

    protected function generatorPreset()
    {
        return $this->laravel[PresetManager::class]->driver($this->option('preset'));
    }
}
