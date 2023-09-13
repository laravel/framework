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
    protected $defaultDriver = 'laravel';

    /**
     * Create "laravel" driver.
     *
     * @return \Laravel
     */
    public function createLaravelDriver()
    {
        return new Presets\Laravel(basePath: $this->container['path.base']);
    }

    /**
     * Set the default driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->defaultDriver = $name;
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }
}
