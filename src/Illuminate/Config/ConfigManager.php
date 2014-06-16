<?php namespace Illuminate\Config;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Manager;

class ConfigManager extends Manager {

    /**
     * The default config loader name.
     *
     * @var array
     */
    protected $defaultLoader;

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  string
     * @return void
     */
    public function __construct($app, $defaultLoader)
    {
        $this->app = $app;
        $this->defaultLoader = $defaultLoader;
    }

    /**
     * Create an instance of the array driver.
     *
     * @return \Illuminate\Config\ArrayLoader
     */
    protected function createArrayDriver()
    {
        return $this->repository(new ArrayFileLoader(new Filesystem, $this->app['path.base']));
    }

    /**
     * Create a new config repository with the given implementation.
     *
     * @param  \Illuminate\Config\LoaderInterface  $loader
     * @return \Illuminate\Config\Repository
     */
    protected function repository(LoaderInterface $loader)
    {
        return new Repository($loader);
    }

    /**
     * Get the default config loader name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultLoader;
    }
}