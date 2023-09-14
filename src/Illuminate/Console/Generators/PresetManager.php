<?php

namespace Illuminate\Console\Generators;

use Illuminate\Support\Manager;

class PresetManager extends Manager
{
    /**
     * The default driver name.
     *
     * @var string
     */
    protected $defaultPreset = 'laravel';

    /**
     * Create "laravel" driver.
     *
     * @return \Illuminate\Console\Generators\Presets\Laravel
     */
    public function createLaravelDriver()
    {
        return new Presets\Laravel($this->container);
    }

    /**
     * Set the default driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->defaultPreset = $name;
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultPreset;
    }
}
