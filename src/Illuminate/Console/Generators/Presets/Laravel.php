<?php

namespace Illuminate\Console\Generators\Presets;

use Illuminate\Contracts\Config\Repository as ConfigContract;

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
     * Preset has custom stub path.
     *
     * @return bool
     */
    public function hasCustomStubPath()
    {
        return true;
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
        return implode('/', [$this->basePath(), 'app']);
    }

    /**
     * Get the path to the view directory.
     *
     * @return string
     */
    public function viewPath()
    {
        return $this->config['view.paths'][0] ?? implode('/', [$this->resourcePath(), 'views']);
    }

    /**
     * Get the path to the seeder directory.
     *
     * @return string
     */
    public function seederPath(): string
    {
        if (is_dir($seederPath = implode('/', [$this->basePath(), 'database', 'seeds']))) {
            return $seederPath;
        }

        return implode('/', [$this->basePath(), 'database', 'seeders']);
    }

    /**
     * Preset namespace.
     *
     * @return string
     */
    public function rootNamespace()
    {
        return trim($this->rootNamespace, '\\').'\\';
    }

    /**
     * Command namespace.
     *
     * @return string
     */
    public function commandNamespace()
    {
        return "{$this->rootNamespace()}Console\Command\\";
    }

    /**
     * Model namespace.
     *
     * @return string
     */
    public function modelNamespace()
    {
        return is_dir("{$this->sourcePath()}/Models") ? "{$this->rootNamespace()}Models\\" : $this->rootNamespace();
    }

    /**
     * Provider namespace.
     *
     * @return string
     */
    public function providerNamespace()
    {
        return "{$this->rootNamespace()}Providers\\";
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
}
