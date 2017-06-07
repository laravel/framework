<?php

namespace Illuminate\Translation;

use Illuminate\Filesystem\Filesystem;

class FileLoader implements LoaderInterface
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The default paths for the loader.
     *
     * @var array
     */
    protected $paths;

    /**
     * All of the namespace hints.
     *
     * @var array
     */
    protected $hints = [];

    /**
     * Create a new file loader instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  array  $paths
     * @return void
     */
    public function __construct(Filesystem $files, $paths)
    {
        $this->paths = array_wrap($paths);
        $this->files = $files;
    }

    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        if ($group == '*' && $namespace == '*') {
            return $this->loadJsonPath($this->paths, $locale);
        }

        if (is_null($namespace) || $namespace == '*') {
            return $this->loadPath($this->paths, $locale, $group);
        }

        return $this->loadNamespaced($locale, $group, $namespace);
    }

    /**
     * Load a namespaced translation group.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    protected function loadNamespaced($locale, $group, $namespace)
    {
        if (isset($this->hints[$namespace])) {
            $lines = $this->loadPath($this->hints[$namespace], $locale, $group);

            return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
        }

        return [];
    }

    /**
     * Load a local namespaced translation group for overrides.
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        foreach ($this->paths as $path) {
            $file = "{$path}/vendor/{$namespace}/{$locale}/{$group}.php";

            if ($this->files->exists($file)) {
                return array_replace_recursive($lines, $this->files->getRequire($file));
            }
        }

        return $lines;
    }

    /**
     * Load a locale from a given paths.
     *
     * @param  array  $paths
     * @param  string  $locale
     * @param  string  $group
     * @return array
     */
    protected function loadPath($paths, $locale, $group)
    {
        $paths = array_wrap($paths);
        foreach ($paths as $path) {
            if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
                return $this->files->getRequire($full);
            }
        }

        return [];
    }

    /**
     * Load a locale from the given JSON file paths.
     *
     * @param  array  $paths
     * @param  string  $locale
     * @return array
     */
    protected function loadJsonPath($paths, $locale)
    {
        $paths = array_wrap($paths);
        foreach ($paths as $path) {
            if ($this->files->exists($full = "{$path}/{$locale}.json")) {
                return json_decode($this->files->get($full), true);
            }
        }

        return [];
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }
}
