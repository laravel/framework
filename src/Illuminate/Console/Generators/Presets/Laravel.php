<?php

namespace Illuminate\Console\Generators\Presets;

class Laravel extends Preset
{
    /**
     * Construct a new preset.
     *
     * @param  string  $rootNamespace
     * @param  string  $basePath
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(
        protected string $rootNamespace,
        string $basePath,
        ConfigContract $config
    ) {
        parent::__construct($basePath, $config);
    }

    /**
     * Preset name.
     *
     * @return string
     */
    public function name()
    {
        return 'laravel';
    }

    /**
     * Get the path to the base working directory.
     *
     * @return string
     */
    public function laravelPath()
    {
        return $this->basePath;
    }

    /**
     * Get the path to the source directory.
     *
     * @return string
     */
    public function sourcePath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'app']);
    }

    /**
     * Get the path to the view directory.
     *
     * @return string
     */
    public function viewPath()
    {
        return $this->config['view.paths'][0] ?? implode(DIRECTORY_SEPARATOR, [$this->resourcePath(), 'views']);
    }

    /**
     * Preset namespace.
     *
     * @return string
     */
    public function rootNamespace()
    {
        return $this->rootNamespace;
    }

    /**
     * Testing namespace.
     *
     * @return string
     */
    public function testingNamespace()
    {
        return 'Tests';
    }

    /**
     * Model namespace.
     *
     * @return string
     */
    public function modelNamespace()
    {
        return "{$this->rootNamespace}\Models";
    }

    /**
     * Provider namespace.
     *
     * @return string
     */
    public function providerNamespace()
    {
        return "{$this->rootNamespace}\Providers";
    }

    /**
     * Get custom stub path.
     *
     * @return string|null
     */
    public function getCustomStubPath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'stubs']);
    }
}
