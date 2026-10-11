<?php

namespace Illuminate\Translation;

use Illuminate\Filesystem\Filesystem;

class CachedFileLoader extends FileLoader
{
    /**
     * The cached translation lines, keyed by locale, namespace, and group.
     *
     * @var array<string, array<string, array<string, array>>>
     */
    protected $cache;

    /**
     * Create a new cached file loader instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  array|string  $path
     * @param  array<string, array<string, array<string, array>>>  $cache
     */
    public function __construct(Filesystem $files, array|string $path, array $cache)
    {
        parent::__construct($files, $path);

        $this->cache = $cache;
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
        if (! isset($this->cache[$locale])) {
            return parent::load($locale, $group, $namespace);
        }

        return $this->cache[$locale][$namespace ?? '*'][$group] ?? [];
    }
}
