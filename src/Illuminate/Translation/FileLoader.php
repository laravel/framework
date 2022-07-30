<?php

namespace Illuminate\Translation;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class FileLoader implements Loader
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The default path for the loader.
     *
     * @var string
     */
    protected $path;

    /**
     * All of the registered paths to JSON translation files.
     *
     * @var array
     */
    protected $jsonPaths = [];

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
     * @param  string  $path
     * @return void
     */
    public function __construct(Filesystem $files, $path)
    {
        $this->path = $path;
        $this->files = $files;
    }

    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        if ($group === '*' && $namespace === '*') {
            return $this->loadJsonPaths($locale);
        }

        if (is_null($namespace) || $namespace === '*') {
            return $this->loadPath($this->path, $locale, $group);
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
        $file = "{$this->path}/vendor/{$namespace}/{$locale}/{$group}.php";

        if ($this->files->exists($file)) {
            return array_replace_recursive($lines, $this->files->getRequire($file));
        }

        return $lines;
    }

    /**
     * Load a locale from a given path.
     *
     * @param  string  $path
     * @param  string  $locale
     * @param  string  $group
     * @return array
     */
    protected function loadPath($path, $locale, $group)
    {
        if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
            return $this->files->getRequire($full);
        }

        return [];
    }

    /**
     * Add all path sub paths
     * including all sub folders inside path 
     * @param string $path
     * @return array
     */
    public function withSubsPaths($paths)
    {
        return collect($paths)
            ->reduce(function ($output, $path) {

                // remove last baclk slash form dir path before pushed
                array_push($output, "{$this->files->dirname($path)}/{$this->files->basename($path)}");

                if ($this->files->exists($path)) {

                    $subs = $this->files->directories($path);
                    foreach ($subs as $sub) {
                        // remove last baclk slash form dir path before pushed
                        array_push($output, "{$this->files->dirname($sub)}/{$this->files->basename($sub)}");
                    }
                }

                return $output;
            }, []);
    }

    /**
     * Add path sub files
     * including all files inside path 
     * @param string $path
     * @param string $locale
     * @param string $extention
     * @return array
     */
    public function withSubsFiles($path, $locale, $extention)
    {
        $files = ["{$path}/{$locale}.{$extention}"];

        if ($this->files->exists("{$path}/{$locale}")) {

            $subFiles = collect($this->files->allFiles("{$path}/{$locale}"))
                ->reduce(function ($output, $file) use ($extention) {

                    if ($this->files->isFile($file) && $file->getExtension() == $extention) {
                        array_push($output, $file->getBasename());
                    }

                    return $output;
                }, []);

            foreach ($subFiles as $file) {
                array_push($files, "{$path}/{$locale}/{$file}");
            }
        }

        return $files;
    }

    /**
     * Load file translations
     * @param string $path
     * @return array
     */

    public function loadFileTranslations($path)
    {
        if ($this->files->exists($path)) {

            $decoded = json_decode($this->files->get($path), true);

            if (is_null($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Translation file [{$path}] contains an invalid JSON structure.");
            }

            return $decoded;
        }
    }

    /**
     * Load a locale from the given JSON file path.
     *
     * @param  string  $locale
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function loadJsonPaths($locale)
    {
        return collect($this->withSubsPaths(array_merge($this->jsonPaths, [$this->path])))
            ->reduce(function ($output, $path) use ($locale) {

                foreach ($this->withSubsFiles($path, $locale, 'json') as $file) {

                    $translations = $this->loadFileTranslations($file);

                    if (is_array($translations)) {
                        $output = array_merge($output, $translations);
                    }
                }


                return $output;
            }, []);
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

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath($path)
    {
        $this->jsonPaths[] = $path;
    }

    /**
     * Get an array of all the registered paths to JSON translation files.
     *
     * @return array
     */
    public function jsonPaths()
    {
        return $this->jsonPaths;
    }
}
