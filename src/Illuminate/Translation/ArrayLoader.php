<?php

namespace Illuminate\Translation;

use Illuminate\Contracts\Translation\Loader;

class ArrayLoader implements Loader
{
    /**
     * All of the translation messages.
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load(string $locale, string $group, string|null $namespace = null): array
    {
        $namespace = $namespace ?: '*';

        return $this->messages[$namespace][$locale][$group] ?? [];
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace(string $namespace, string $hint): void
    {
        //
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath(string $path): void
    {
        //
    }

    /**
     * Add messages to the loader.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  array  $messages
     * @param  string|null  $namespace
     * @return $this
     */
    public function addMessages(string $locale, string $group, array $messages, string|null $namespace = null): self
    {
        $namespace = $namespace ?: '*';

        $this->messages[$namespace][$locale][$group] = $messages;

        return $this;
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces(): array
    {
        return [];
    }
}
